<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Models\Tenant;
use App\Policies\TenantPolicy;
use App\Services\PortalAccountService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages Tenant records (renters). See {@see TenantPolicy}:
 * admin-only — a tenant's own portal access is governed elsewhere. Setting a
 * password on store/update creates or updates a dedicated login for the
 * tenant — see {@see PortalAccountService}. Unlike Owner/Receiver, this form
 * doesn't offer linking to an *existing* user; tenants aren't expected to
 * double as Admin/Receiver/Owner, so there's no such account to link to.
 */
class TenantController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Tenant::class);

        return Inertia::render('admin/tenants/Index', [
            'tenants' => Tenant::query()
                ->with('user')
                ->orderBy('name')
                ->paginate(Pagination::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Tenant::class);

        return Inertia::render('admin/tenants/Form', [
            'tenant' => null,
        ]);
    }

    public function store(StoreTenantRequest $request, PortalAccountService $portalAccounts): RedirectResponse
    {
        $this->authorize('create', Tenant::class);

        $userId = $portalAccounts->sync(
            role: UserRole::Tenant,
            currentUserId: null,
            existingUserId: null,
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
        );

        Tenant::query()->create([
            ...$request->safe()->only(['name', 'document', 'email', 'whatsapp', 'status', 'resident_count']),
            'user_id' => $userId,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Inquilino cadastrado.']);

        return to_route('admin.tenants.index');
    }

    public function edit(Tenant $tenant): Response
    {
        $this->authorize('update', $tenant);

        return Inertia::render('admin/tenants/Form', [
            'tenant' => $tenant->load('user'),
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant, PortalAccountService $portalAccounts): RedirectResponse
    {
        $this->authorize('update', $tenant);

        $tenant->update($request->safe()->only(['name', 'document', 'email', 'whatsapp', 'status', 'resident_count']));

        $userId = $portalAccounts->sync(
            role: UserRole::Tenant,
            currentUserId: $tenant->user_id,
            existingUserId: null,
            name: $tenant->name,
            email: $tenant->email,
            password: $request->string('password')->toString(),
        );

        if ($userId !== $tenant->user_id) {
            $tenant->update(['user_id' => $userId]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Inquilino atualizado.']);

        return to_route('admin.tenants.index');
    }

    public function destroy(Tenant $tenant, PortalAccountService $portalAccounts): RedirectResponse
    {
        $this->authorize('delete', $tenant);

        if ($tenant->contracts()->whereIn('status', [ContractStatus::Active, ContractStatus::Expiring])->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Não é possível excluir um inquilino com contrato ativo.',
            ]);

            return back();
        }

        $userId = $tenant->user_id;

        $tenant->delete();

        if ($userId !== null) {
            $portalAccounts->detach($userId, UserRole::Tenant);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Inquilino removido.']);

        return to_route('admin.tenants.index');
    }
}
