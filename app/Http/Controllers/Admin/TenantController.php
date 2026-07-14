<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Tenant::class);

        return Inertia::render('admin/tenants/Index', [
            'tenants' => Tenant::query()->with('user')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Tenant::class);

        return Inertia::render('admin/tenants/Form', [
            'tenant' => null,
        ]);
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $this->authorize('create', Tenant::class);

        $userId = null;
        if ($request->filled('password')) {
            $user = User::query()->create([
                'name' => $request->string('name'),
                'email' => $request->string('email'),
                'password' => Hash::make($request->string('password')),
            ]);
            $user->assignRole(UserRole::Tenant);
            $userId = $user->id;
        }

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

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('update', $tenant);

        $tenant->update($request->safe()->only(['name', 'document', 'email', 'whatsapp', 'status', 'resident_count']));

        if ($request->filled('password')) {
            if (! $tenant->user_id) {
                $user = User::query()->create([
                    'name' => $tenant->name,
                    'email' => $tenant->email,
                    'password' => Hash::make($request->string('password')),
                ]);
                $user->assignRole(UserRole::Tenant);
                $tenant->update(['user_id' => $user->id]);
            } else {
                $tenant->user?->update(['password' => Hash::make($request->string('password'))]);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Inquilino atualizado.']);

        return to_route('admin.tenants.index');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $this->authorize('delete', $tenant);

        $tenant->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Inquilino removido.']);

        return to_route('admin.tenants.index');
    }
}
