<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOwnerRequest;
use App\Http\Requests\Admin\UpdateOwnerRequest;
use App\Models\Owner;
use App\Models\Property;
use App\Policies\OwnerPolicy;
use App\Services\PortalAccountService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages Owner records (property owners). See {@see OwnerPolicy}:
 * admin-only. An Owner can optionally have portal access via a linked User —
 * either a brand-new dedicated login, or an existing account (e.g. the same
 * login already used to sign in as Admin/Receiver) — see
 * {@see PortalAccountService} and docs/adr/0006-shared-login-across-roles.md.
 */
class OwnerController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Owner::class);

        return Inertia::render('admin/owners/Index', [
            'owners' => Owner::query()
                ->with('user')
                ->withCount('properties')
                ->orderBy('name')
                ->paginate(Pagination::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function create(PortalAccountService $portalAccounts): Response
    {
        $this->authorize('create', Owner::class);

        return Inertia::render('admin/owners/Form', [
            'owner' => null,
            'properties' => Property::query()->orderBy('name')->get(),
            'users' => $portalAccounts->linkableUsers(),
        ]);
    }

    public function store(StoreOwnerRequest $request, PortalAccountService $portalAccounts): RedirectResponse
    {
        $this->authorize('create', Owner::class);

        $owner = Owner::query()->create($request->safe()->only(['name', 'document', 'email', 'phone']));
        $owner->properties()->sync($request->input('property_ids', []));

        $userId = $portalAccounts->sync(
            role: UserRole::Owner,
            currentUserId: null,
            existingUserId: $request->integer('existing_user_id') ?: null,
            name: $owner->name,
            email: $owner->email,
            password: $request->string('password')->toString(),
        );

        if ($userId !== null) {
            $owner->update(['user_id' => $userId]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proprietario cadastrado.']);

        return to_route('admin.owners.index');
    }

    public function edit(Owner $owner, PortalAccountService $portalAccounts): Response
    {
        $this->authorize('update', $owner);

        $owner->load(['properties', 'user.roles']);

        return Inertia::render('admin/owners/Form', [
            'owner' => [
                ...$owner->toArray(),
                'user' => $owner->user ? [
                    'id' => $owner->user->id,
                    'name' => $owner->user->name,
                    'email' => $owner->user->email,
                    'roles' => $owner->user->roles->pluck('name')->all(),
                ] : null,
            ],
            'properties' => Property::query()->orderBy('name')->get(),
            'users' => $portalAccounts->linkableUsers(),
        ]);
    }

    public function update(UpdateOwnerRequest $request, Owner $owner, PortalAccountService $portalAccounts): RedirectResponse
    {
        $this->authorize('update', $owner);

        $owner->update($request->safe()->only(['name', 'document', 'email', 'phone']));
        $owner->properties()->sync($request->input('property_ids', []));

        $oldUserId = $owner->user_id;
        $userId = $portalAccounts->sync(
            role: UserRole::Owner,
            currentUserId: $oldUserId,
            existingUserId: $request->integer('existing_user_id') ?: null,
            name: $owner->name,
            email: $owner->email,
            password: $request->string('password')->toString(),
        );

        if ($userId !== $oldUserId) {
            $owner->update(['user_id' => $userId]);

            if ($oldUserId !== null) {
                $portalAccounts->detach($oldUserId, UserRole::Owner);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proprietario atualizado.']);

        return to_route('admin.owners.index');
    }

    public function destroy(Owner $owner, PortalAccountService $portalAccounts): RedirectResponse
    {
        $this->authorize('delete', $owner);

        $userId = $owner->user_id;

        $owner->delete();

        if ($userId !== null) {
            $portalAccounts->detach($userId, UserRole::Owner);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proprietario removido.']);

        return to_route('admin.owners.index');
    }
}
