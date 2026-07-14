<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/admins/Index', [
            'admins' => User::role(UserRole::Admin)->orderBy('name')->get(['id', 'name', 'email', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/admins/Form', [
            'admin' => null,
        ]);
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => Hash::make($request->string('password')),
        ]);
        $user->assignRole(UserRole::Admin);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Administrador cadastrado.']);

        return to_route('admin.admins.index');
    }

    public function edit(User $admin): RedirectResponse|Response
    {
        abort_unless($admin->hasRole(UserRole::Admin), 404);

        return Inertia::render('admin/admins/Form', [
            'admin' => $admin->only(['id', 'name', 'email']),
        ]);
    }

    public function update(UpdateAdminUserRequest $request, User $admin): RedirectResponse
    {
        abort_unless($admin->hasRole(UserRole::Admin), 404);

        $admin->update($request->safe()->only(['name', 'email']));

        if ($request->filled('password')) {
            $admin->update(['password' => Hash::make($request->string('password'))]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Administrador atualizado.']);

        return to_route('admin.admins.index');
    }

    public function destroy(Request $request, User $admin): RedirectResponse
    {
        abort_unless($admin->hasRole(UserRole::Admin), 404);
        abort_if($admin->id === $request->user()?->id, 403);

        $admin->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Administrador removido.']);

        return to_route('admin.admins.index');
    }
}
