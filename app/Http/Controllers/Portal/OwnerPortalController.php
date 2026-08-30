<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Owner;
use App\Models\Property;
use App\Models\User;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only self-service portal for a logged-in owner: lists only the
 * properties and contracts tied to the Owner record(s) linked to their
 * User. A User can be linked from more than one Owner row (co-ownership
 * sharing one login — see PortalAccountService), so this aggregates across
 * all of them rather than assuming exactly one.
 */
class OwnerPortalController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $ownerIds = Owner::query()->where('user_id', $user->id)->pluck('id');
        abort_if($ownerIds->isEmpty(), 403);

        $propertyIds = Owner::query()
            ->whereIn('id', $ownerIds)
            ->with('properties:id')
            ->get()
            ->pluck('properties')
            ->flatten()
            ->pluck('id')
            ->unique();

        return Inertia::render('portal/Owner', [
            'properties' => Property::query()
                ->whereIn('id', $propertyIds)
                ->orderBy('name')
                ->paginate(Pagination::PER_PAGE, pageName: 'properties')
                ->withQueryString()
                ->through(fn (Property $property) => [
                    'id' => $property->id,
                    'name' => $property->name,
                    'address' => $property->address,
                    'type_label' => $property->type?->label(),
                    'status' => $property->status?->value,
                ]),
            'contracts' => Contract::query()
                ->with(['property', 'tenant'])
                ->whereIn('property_id', $propertyIds)
                ->orderByDesc('starts_at')
                ->paginate(Pagination::PER_PAGE, pageName: 'contracts')
                ->withQueryString()
                ->through(fn (Contract $contract) => [
                    'id' => $contract->id,
                    'status' => $contract->status?->value,
                    'monthly_rent' => (float) $contract->monthly_rent,
                    'starts_at' => $contract->starts_at?->toDateString(),
                    'ends_at' => $contract->ends_at?->toDateString(),
                    'property' => $contract->property ? [
                        'id' => $contract->property->id,
                        'name' => $contract->property->name,
                    ] : null,
                    'tenant' => $contract->tenant ? [
                        'id' => $contract->tenant->id,
                        'name' => $contract->tenant->name,
                    ] : null,
                ]),
        ]);
    }
}
