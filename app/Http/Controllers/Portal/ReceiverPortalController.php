<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ChargeStatus;
use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\User;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only self-service portal for a logged-in receiver: lists only the
 * contracts and charges tied to their own `receiver` record.
 */
class ReceiverPortalController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $receiver = $user->receiver;
        abort_unless($receiver, 403);

        return Inertia::render('portal/Receiver', [
            'contracts' => Contract::query()
                ->with(['property', 'tenant'])
                ->where('receiver_id', $receiver->id)
                ->orderByDesc('starts_at')
                ->paginate(Pagination::PER_PAGE, pageName: 'contracts')
                ->withQueryString()
                ->through(fn (Contract $contract) => [
                    'id' => $contract->id,
                    'status' => $contract->status?->value,
                    'monthly_rent' => (float) $contract->monthly_rent,
                    'property' => $contract->property ? [
                        'id' => $contract->property->id,
                        'name' => $contract->property->name,
                    ] : null,
                    'tenant' => $contract->tenant ? [
                        'id' => $contract->tenant->id,
                        'name' => $contract->tenant->name,
                    ] : null,
                ]),
            'charges' => Charge::query()
                ->with(['contract.property', 'contract.tenant'])
                ->where('receiver_id', $receiver->id)
                ->orderByDesc('due_date')
                ->paginate(Pagination::PER_PAGE, pageName: 'charges')
                ->withQueryString()
                ->through(fn (Charge $charge) => [
                    'id' => $charge->id,
                    'description' => $charge->reference,
                    'amount' => (float) $charge->original_amount,
                    'rateio_amount' => (float) ($charge->rateio_amount ?? 0),
                    'status' => $charge->status?->value ?? $charge->status,
                    'due_date' => $charge->due_date?->toDateString(),
                    'is_paid' => $charge->status === ChargeStatus::Paid,
                    'tenant' => $charge->contract?->tenant?->name,
                    'property' => $charge->contract?->property?->name,
                ]),
        ]);
    }
}
