<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ChargeStatus;
use App\Enums\ContractStatus;
use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\Tenant;
use App\Services\Finance;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders the admin home dashboard: aggregate financial stats (expected,
 * received, open, overdue amounts) computed from all Charges via
 * {@see Finance::computeAmountDue()}, a per-receiver
 * breakdown, and recent charges/active contracts. No dedicated Policy;
 * gated only by the `role:admin` route middleware.
 */
class DashboardController extends Controller
{
    public function index(): Response
    {
        $openStatuses = [ChargeStatus::Open, ChargeStatus::WaitingPayment, ChargeStatus::Overdue];
        $charges = Charge::query()
            ->with(['contract.tenant', 'contract.property', 'contract.receiver', 'receiver'])
            ->get();

        $expected = 0.0;
        $received = 0.0;
        $open = 0.0;
        $overdue = 0.0;
        $byReceiver = [];

        foreach ($charges as $charge) {
            $amountDue = Finance::computeAmountDue([
                'originalAmount' => (float) $charge->original_amount,
                'dueDate' => $charge->due_date->format('Y-m-d'),
                'status' => $charge->status?->value ?? (string) $charge->status,
                'graceDays' => (int) ($charge->contract?->grace_days ?? 0),
                'fineRate' => (float) ($charge->contract?->fine_rate ?? 0),
                'monthlyInterestRate' => (float) ($charge->contract?->monthly_interest_rate ?? 0),
            ]);

            $receiverName = $charge->receiver?->name ?? $charge->contract?->receiver?->name ?? 'Sem recebedor';
            $byReceiver[$receiverName] ??= ['expected' => 0.0, 'received' => 0.0, 'open' => 0.0];

            $expected += $amountDue;
            $byReceiver[$receiverName]['expected'] += $amountDue;

            if ($charge->status === ChargeStatus::Paid) {
                $received += (float) $charge->original_amount;
                $byReceiver[$receiverName]['received'] += (float) $charge->original_amount;
            } elseif (in_array($charge->status, $openStatuses, true)) {
                $open += $amountDue;
                $byReceiver[$receiverName]['open'] += $amountDue;

                if ($charge->status === ChargeStatus::Overdue || $charge->due_date->isPast()) {
                    $overdue += $amountDue;
                }
            }
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'expected' => round($expected, 2),
                'received' => round($received, 2),
                'open' => round($open, 2),
                'overdue' => round($overdue, 2),
                'openCharges' => Charge::query()->whereIn('status', $openStatuses)->count(),
                'activeContracts' => Contract::query()
                    ->whereIn('status', [ContractStatus::Active, ContractStatus::Expiring])
                    ->count(),
                'tenantsCount' => Tenant::query()->count(),
            ],
            'byReceiver' => collect($byReceiver)
                ->map(fn (array $row, string $name) => [
                    'name' => $name,
                    'expected' => round($row['expected'], 2),
                    'received' => round($row['received'], 2),
                    'open' => round($row['open'], 2),
                ])
                ->values()
                ->all(),
            'recentCharges' => Charge::query()
                ->with(['contract.tenant', 'contract.property'])
                ->latest('due_date')
                ->limit(10)
                ->get()
                ->map(fn (Charge $charge) => [
                    'id' => $charge->id,
                    'description' => $charge->reference,
                    'amount' => (float) $charge->original_amount,
                    'status' => $charge->status?->value ?? $charge->status,
                    'due_date' => $charge->due_date?->toDateString(),
                    'tenant' => $charge->contract?->tenant
                        ? ['name' => $charge->contract->tenant->name]
                        : null,
                    'property' => $charge->contract?->property
                        ? ['name' => $charge->contract->property->name]
                        : null,
                ]),
            'activeContracts' => Contract::query()
                ->with(['property', 'tenant', 'receiver'])
                ->whereIn('status', [ContractStatus::Active, ContractStatus::Expiring])
                ->orderByDesc('starts_at')
                ->limit(8)
                ->get()
                ->map(fn (Contract $contract) => [
                    'id' => $contract->id,
                    'status' => $contract->status?->value,
                    'monthly_rent' => (float) $contract->monthly_rent,
                    'property' => $contract->property?->name,
                    'tenant' => $contract->tenant?->name,
                    'receiver' => $contract->receiver?->name,
                ]),
        ]);
    }
}
