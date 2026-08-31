<?php

namespace App\Services;

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\Contract;
use App\Notifications\ChargeReminderNotification;
use App\Notifications\ContractExpiringNotification;
use App\Notifications\PaymentConfirmedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ReminderService
{
    private const BEFORE_DUE_LEAD_DAYS = 5;

    private const AFTER_DUE_RESEND_DAYS = 3;

    public function __construct(
        private MercadoPagoService $mercadoPagoService,
    ) {}

    /**
     * @return array{sent: int, skipped: int, failed: int}
     */
    public function runReminderSweep(): array
    {
        $today = BillingCycle::todayInSaoPaulo();
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        app(ChargeScheduler::class)->markOverdueCharges();

        $charges = Charge::query()
            ->whereIn('status', [ChargeStatus::Open, ChargeStatus::WaitingPayment, ChargeStatus::Overdue])
            ->get();

        foreach ($charges as $charge) {
            $diffDays = $this->diffDaysFromToday($charge->due_date->format('Y-m-d'), $today);
            $desiredEvent = match (true) {
                $diffDays < 0 => 'after_due',
                $diffDays === 0 => 'due_day',
                $diffDays === self::BEFORE_DUE_LEAD_DAYS => 'before_due',
                default => null,
            };

            if (! $desiredEvent) {
                $skipped++;

                continue;
            }

            if ($desiredEvent === $charge->last_reminder_event) {
                if ($desiredEvent !== 'after_due') {
                    $skipped++;

                    continue;
                }

                // phpstan claims last_reminder_sent_at is never null here,
                // but the column is genuinely nullable (no reminder sent
                // yet) — keep the nullsafe.
                $lastSentAt = (int) ($charge->last_reminder_sent_at?->timestamp ?? 0); // @phpstan-ignore nullsafe.neverNull
                if ((int) now()->timestamp - $lastSentAt < self::AFTER_DUE_RESEND_DAYS * 86_400) {
                    $skipped++;

                    continue;
                }
            }

            try {
                $this->sendChargeReminder($charge);
                $sent++;
            } catch (\Throwable $exception) {
                $failed++;
                Log::error('[reminders] failed to send charge reminder', [
                    'charge_id' => $charge->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return compact('sent', 'skipped', 'failed');
    }

    /**
     * @return array{event: string, tenantName: string}
     */
    public function sendChargeReminder(Charge $charge): array
    {
        $context = $this->getChargeReminderContext($charge);
        $tenant = $charge->contract?->tenant;

        if (! $tenant) {
            throw new \InvalidArgumentException('Cobranca sem inquilino vinculado.');
        }

        if (! filled($tenant->email) && ! filled($tenant->whatsapp)) {
            throw new \InvalidArgumentException('Este inquilino nao tem email nem WhatsApp cadastrado.');
        }

        $event = $this->determineReminderEvent($context)['event'];
        $daysLate = $this->determineReminderEvent($context)['daysLate'] ?? null;

        $tenant->notify(new ChargeReminderNotification($charge, [
            'event' => $event,
            'tenantName' => $context['tenantName'],
            'amount' => $context['amountDue'],
            'dueDate' => $this->formatDatePtBr($context['dueDateIso']),
            'paymentUrl' => $this->buildPaymentUrl(),
            'propertyName' => $context['propertyName'],
            'daysLate' => $daysLate,
        ]));

        $charge->update([
            'last_reminder_event' => $event,
            'last_reminder_sent_at' => now(),
        ]);

        return ['event' => $event, 'tenantName' => $context['tenantName']];
    }

    public function sendPaymentConfirmedReminder(Charge $charge): void
    {
        $charge->loadMissing(['contract.tenant', 'contract.property', 'contract.receiver']);

        $notifiables = collect([
            $charge->contract?->tenant,
            $charge->contract?->receiver,
        ])->filter();

        if ($notifiables->isNotEmpty()) {
            Notification::send($notifiables, new PaymentConfirmedNotification($charge));
        }

        $charge->update([
            'last_reminder_event' => 'payment_confirmed',
            'last_reminder_sent_at' => now(),
        ]);
    }

    public function sendContractExpiringReminder(Contract $contract): bool
    {
        $contract->loadMissing(['tenant', 'property']);

        $tenant = $contract->tenant;

        if ($contract->expiring_reminder_sent_at || ! $tenant) {
            return false;
        }

        if (! filled($tenant->email) && ! filled($tenant->whatsapp)) {
            return false;
        }

        $tenant->notify(new ContractExpiringNotification($contract));

        $contract->update(['expiring_reminder_sent_at' => now()]);

        return true;
    }

    /**
     * @return array{status: string, dueDateIso: string, amountDue: float, tenantName: string, tenantPhone: string|null, propertyName: string, receiverName: string}
     */
    private function getChargeReminderContext(Charge $charge): array
    {
        $charge->loadMissing(['contract.tenant', 'contract.property', 'receiver']);

        return [
            'status' => $charge->status->value,
            'dueDateIso' => $charge->due_date->format('Y-m-d'),
            'amountDue' => $this->mercadoPagoService->computeCurrentAmountDue($charge),
            'tenantName' => $charge->contract->tenant->name,
            'tenantPhone' => $charge->contract->tenant->whatsapp,
            'propertyName' => $charge->contract->property->name,
            'receiverName' => $charge->receiver->name,
        ];
    }

    /**
     * @param  array{status: string, dueDateIso: string}  $context
     * @return array{event: string, daysLate?: int}
     */
    private function determineReminderEvent(array $context): array
    {
        if ($context['status'] === ChargeStatus::Paid->value) {
            return ['event' => 'payment_confirmed'];
        }

        $diffDays = $this->diffDaysFromToday($context['dueDateIso'], BillingCycle::todayInSaoPaulo());

        if ($diffDays < 0) {
            return ['event' => 'after_due', 'daysLate' => abs($diffDays)];
        }

        if ($diffDays === 0) {
            return ['event' => 'due_day'];
        }

        return ['event' => 'before_due'];
    }

    private function diffDaysFromToday(string $dueDateIso, string $todayIso): int
    {
        $due = strtotime($dueDateIso.'T12:00:00-03:00');
        $today = strtotime($todayIso.'T12:00:00-03:00');

        return (int) round(($due - $today) / 86_400);
    }

    private function formatDatePtBr(string $dateIso): string
    {
        $date = now()->parse($dateIso.'T12:00:00-03:00')->timezone('America/Sao_Paulo');
        $date->locale('pt_BR');

        return $date->translatedFormat('d/m/Y');
    }

    private function buildPaymentUrl(): string
    {
        return rtrim((string) config('services.app_base_url'), '/').'/portal/tenant';
    }
}
