<?php

namespace App\Services;

use App\Enums\ChargeStatus;
use App\Enums\ContractStatus;
use App\Models\BillingSetting;
use App\Models\Charge;
use App\Models\Contract;
use Illuminate\Support\Carbon;

/**
 * Two distinct entry points, deliberately gated differently:
 * {@see generateChargeForContract()} is the admin's on-demand "gerar
 * cobrança" button — always allowed, no day restriction. {@see
 * runMonthlyChargeSweep()} is the scheduled daily sweep, gated by
 * {@see hasReachedGenerationDay()} against the configurable
 * {@see BillingSetting} — see
 * docs/adr/0007-configurable-charge-generation-day.md for why that
 * generation day is a single global setting decoupled from each
 * Contract's own due date, and how the two interact.
 */
class ChargeScheduler
{
    public function __construct(private RateioService $rateioService) {}

    /**
     * @return array{created: bool, updated: bool, chargeId?: int, reference: string}
     */
    public function generateChargeForContract(Contract $contract): array
    {
        $contract->loadMissing('property');

        $today = BillingCycle::todayInSaoPaulo();
        $cycle = BillingCycle::resolveBillingCycleDueDate($contract->due_day, $today);
        $reference = BillingCycle::formatReference($cycle['dueDateIso']);

        $existing = Charge::query()
            ->where('contract_id', $contract->id)
            ->where('reference', $reference)
            ->first();

        if ($existing) {
            if ($existing->status === ChargeStatus::Paid) {
                return ['created' => false, 'updated' => false, 'reference' => $reference];
            }

            $existing->update([
                'original_amount' => $contract->monthly_rent + ($existing->rateio_amount ?? 0),
                'due_date' => $cycle['dueDateIso'],
                ...$this->clearedPixFields(),
            ]);

            return [
                'created' => false,
                'updated' => true,
                'chargeId' => $existing->id,
                'reference' => $reference,
            ];
        }

        $charge = Charge::query()->create([
            'contract_id' => $contract->id,
            'receiver_id' => $contract->receiver_id,
            'reference' => $reference,
            'due_date' => $cycle['dueDateIso'],
            'original_amount' => $contract->monthly_rent,
            'status' => ChargeStatus::Open,
        ]);

        $this->rateioService->applyPendingRateioAllocations(
            $contract->property_id,
            $reference,
            $charge,
        );

        return [
            'created' => true,
            'updated' => false,
            'chargeId' => $charge->id,
            'reference' => $reference,
        ];
    }

    /**
     * @return array{created: int, skipped: int}
     */
    public function runMonthlyChargeSweep(): array
    {
        $today = BillingCycle::todayInSaoPaulo();
        $created = 0;
        $skipped = 0;

        if (! $this->hasReachedGenerationDay($today)) {
            $this->markOverdueCharges();

            return ['created' => $created, 'skipped' => $skipped];
        }

        $contracts = Contract::query()
            ->whereIn('status', [ContractStatus::Active, ContractStatus::Expiring])
            ->get();

        foreach ($contracts as $contract) {
            $cycle = BillingCycle::resolveBillingCycleDueDate($contract->due_day, $today);
            $reference = BillingCycle::formatReference($cycle['dueDateIso']);

            if (Charge::query()->where('contract_id', $contract->id)->where('reference', $reference)->exists()) {
                $skipped++;

                continue;
            }

            $charge = Charge::query()->create([
                'contract_id' => $contract->id,
                'receiver_id' => $contract->receiver_id,
                'reference' => $reference,
                'due_date' => $cycle['dueDateIso'],
                'original_amount' => $contract->monthly_rent,
                'status' => ChargeStatus::Open,
            ]);

            $this->rateioService->applyPendingRateioAllocations(
                $contract->property_id,
                $reference,
                $charge,
            );

            $created++;
        }

        $this->markOverdueCharges();

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function markOverdueCharges(): int
    {
        return Charge::query()
            ->whereIn('status', [ChargeStatus::Open, ChargeStatus::WaitingPayment])
            ->whereDate('due_date', '<', BillingCycle::todayInSaoPaulo())
            ->update(['status' => ChargeStatus::Overdue]);
    }

    /**
     * True from the configured {@see BillingSetting::$generation_day} of the
     * month onward, so a missed run (server down, deploy window) still
     * catches up on later days instead of skipping the whole month.
     */
    private function hasReachedGenerationDay(string $todayIso): bool
    {
        return Carbon::parse($todayIso)->day >= BillingSetting::current()->generation_day;
    }

    /**
     * @return array<string, null>
     */
    private function clearedPixFields(): array
    {
        return [
            'mercado_pago_order_id' => null,
            'mercado_pago_transaction_id' => null,
            'payment_url' => null,
            'pix_qr_code' => null,
            'pix_qr_code_base64' => null,
            'pix_expires_at' => null,
        ];
    }
}
