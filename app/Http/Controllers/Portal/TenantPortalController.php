<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ChargeStatus;
use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\User;
use App\Services\MercadoPagoService;
use App\Support\Money;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantPortalController extends Controller
{
    public function index(Request $request, MercadoPagoService $mercadoPago): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $tenant = $user->tenant;
        abort_unless($tenant, 403);

        return Inertia::render('portal/Tenant', [
            'contracts' => Contract::query()
                ->with(['property', 'receiver'])
                ->where('tenant_id', $tenant->id)
                ->orderByDesc('starts_at')
                ->paginate(Pagination::PER_PAGE, pageName: 'contracts')
                ->withQueryString()
                ->through(fn (Contract $contract) => [
                    'id' => $contract->id,
                    'status' => $contract->status?->value,
                    'signature_status' => $contract->signature_status?->value,
                    'monthly_rent' => (float) $contract->monthly_rent,
                    'starts_at' => $contract->starts_at?->toDateString(),
                    'ends_at' => $contract->ends_at?->toDateString(),
                    'property' => $contract->property ? [
                        'id' => $contract->property->id,
                        'name' => $contract->property->name,
                    ] : null,
                ]),
            'charges' => Charge::query()
                ->with(['contract.property', 'contract', 'receiver'])
                ->whereHas('contract', fn ($query) => $query->where('tenant_id', $tenant->id))
                ->orderByDesc('due_date')
                ->paginate(Pagination::PER_PAGE, pageName: 'charges')
                ->withQueryString()
                ->through(function (Charge $charge) use ($mercadoPago) {
                    $originalAmount = (float) $charge->original_amount;
                    $amountDue = Money::roundCents($mercadoPago->computeCurrentAmountDue($charge));

                    return [
                        'id' => $charge->id,
                        'description' => $charge->reference,
                        'amount' => $originalAmount,
                        'amount_due' => $amountDue,
                        'has_penalties' => $amountDue > $originalAmount,
                        'rateio_amount' => (float) ($charge->rateio_amount ?? 0),
                        'status' => $charge->status?->value ?? $charge->status,
                        'due_date' => $charge->due_date?->toDateString(),
                        'is_paid' => $charge->status === ChargeStatus::Paid,
                        'property' => $charge->contract?->property?->name,
                        'pix_qr_code' => $charge->pix_qr_code,
                        'pix_qr_code_base64' => $charge->pix_qr_code_base64,
                        'pix_expires_at' => $charge->pix_expires_at?->toIso8601String(),
                        'has_pix' => filled($charge->pix_qr_code) || filled($charge->pix_qr_code_base64),
                    ];
                }),
        ]);
    }
}
