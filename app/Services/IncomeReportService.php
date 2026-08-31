<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Builds the "Informe de Rendimentos": net income actually received per
 * month, for a given year (and optionally a single month within it, plus
 * an owner/receiver filter). "Received" means an Approved Payment's
 * `net_amount` (already net of Mercado Pago fees) — see CONTEXT.md's
 * Pagamento entry. Only rent Charge payments count; Deposit (Caução)
 * payments are excluded on purpose, since a caução is a refundable
 * security hold, not taxable rental income.
 *
 * Aggregates in PHP rather than a SQL GROUP BY on a formatted date: the
 * app already fetches-then-loops for this kind of report (see
 * DashboardController), and it sidesteps writing per-driver date-format
 * SQL for a query that, at this app's scale, never needs to run over
 * more rows than a year of payments.
 */
class IncomeReportService
{
    /**
     * @return array{months: list<array{reference: string, month: int, label: string, total: float, count: int}>, total: float, payments: list<array<string, mixed>>}
     */
    public function summarize(int $year, ?int $month = null, ?int $ownerId = null, ?int $receiverId = null): array
    {
        $payments = Payment::query()
            ->where('status', PaymentStatus::Approved)
            ->whereNotNull('charge_id')
            ->whereYear('paid_at', $year)
            ->when($month, fn ($query) => $query->whereMonth('paid_at', $month))
            ->when($receiverId, fn ($query) => $query->whereHas(
                'charge',
                fn ($q) => $q->where('receiver_id', $receiverId),
            ))
            ->when($ownerId, fn ($query) => $query->whereHas(
                'charge.contract.property.owners',
                fn ($q) => $q->where('owners.id', $ownerId),
            ))
            ->with(['charge.contract.tenant', 'charge.contract.property', 'charge.receiver'])
            ->orderBy('paid_at')
            ->get();

        return [
            'months' => $this->buildMonths($year, $month, $payments),
            'total' => Money::roundCents((float) $payments->sum(fn (Payment $payment) => (float) $payment->net_amount)),
            'payments' => array_values($payments->map(fn (Payment $payment) => $this->presentPayment($payment))->all()),
        ];
    }

    /**
     * @return list<int>
     */
    public function availableYears(): array
    {
        $currentYear = (int) now()->year;

        $earliestPaidAt = Payment::query()
            ->where('status', PaymentStatus::Approved)
            ->whereNotNull('charge_id')
            ->whereNotNull('paid_at')
            ->min('paid_at');

        $earliestYear = $earliestPaidAt ? (int) date('Y', strtotime($earliestPaidAt)) : $currentYear;

        return range(max($earliestYear, $currentYear - 10), $currentYear);
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @return list<array{reference: string, month: int, label: string, total: float, count: int}>
     */
    private function buildMonths(int $year, ?int $month, Collection $payments): array
    {
        /** @var Collection<string, Collection<int, Payment>> $byMonth */
        $byMonth = $payments->groupBy(fn (Payment $payment): string => (string) $payment->paid_at?->format('Y-m'));
        $monthNumbers = $month ? [$month] : range(1, 12);

        return array_map(function (int $monthNumber) use ($year, $byMonth) {
            $reference = sprintf('%04d-%02d', $year, $monthNumber);
            /** @var Collection<int, Payment> $group */
            $group = $byMonth->get($reference, collect());

            return [
                'reference' => $reference,
                'month' => $monthNumber,
                'label' => (self::MONTH_NAMES[$monthNumber] ?? (string) $monthNumber).'/'.$year,
                'total' => Money::roundCents((float) $group->sum(fn (Payment $payment) => (float) $payment->net_amount)),
                'count' => $group->count(),
            ];
        }, $monthNumbers);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentPayment(Payment $payment): array
    {
        $charge = $payment->charge;

        return [
            'id' => $payment->id,
            'paid_at' => $payment->paid_at?->toDateString(),
            'net_amount' => (float) $payment->net_amount,
            'amount_paid' => (float) $payment->amount_paid,
            'fees' => (float) $payment->fees,
            'method' => $payment->method->value,
            'reference' => $charge?->reference,
            'tenant' => $charge?->contract?->tenant?->name,
            'property' => $charge?->contract?->property?->name,
            'receiver' => $charge?->receiver?->name,
        ];
    }

    /**
     * @var array<int, string>
     */
    private const MONTH_NAMES = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];
}
