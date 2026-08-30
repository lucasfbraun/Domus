<?php

use App\Enums\ContractStatus;
use App\Enums\UserRole;
use App\Models\Contract;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin can create a tenant with portal password confirmation', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.tenants.store'), [
            'name' => 'Inquilino Portal',
            'document' => '52998224725',
            'email' => 'tenant-portal@example.com',
            'whatsapp' => '5511999990000',
            'status' => 'active',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('admin.tenants.index'))
        ->assertSessionDoesntHaveErrors();

    $tenant = Tenant::query()->where('email', 'tenant-portal@example.com')->first();

    expect($tenant)->not->toBeNull()
        ->and($tenant->user_id)->not->toBeNull();

    $user = User::query()->find($tenant->user_id);

    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('tenant-portal@example.com')
        ->and($user->hasRole(UserRole::Tenant))->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('a tenant created by the admin can actually log in to their portal without an email verification wall', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.tenants.store'), [
        'name' => 'Inquilino Login',
        'document' => '52998224725',
        'email' => 'tenant-login@example.com',
        'whatsapp' => '5511999990000',
        'status' => 'active',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $tenant = Tenant::query()->where('email', 'tenant-login@example.com')->firstOrFail();
    $user = User::query()->findOrFail($tenant->user_id);

    expect($user->hasVerifiedEmail())->toBeTrue();

    // The `verified` middleware wraps every authenticated route (routes/web.php)
    // — an unverified account would get redirected to Fortify's
    // verify-email notice instead of reaching the portal.
    $this->actingAs($user)
        ->get(route('tenant.portal'))
        ->assertSuccessful();
});

test('admin cannot create a tenant whose portal email is already used by another account', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'ja-existe@example.com']);

    $this->actingAs($admin)
        ->from(route('admin.tenants.create'))
        ->post(route('admin.tenants.store'), [
            'name' => 'Inquilino Duplicado',
            'document' => '52998224725',
            'email' => 'ja-existe@example.com',
            'status' => 'active',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('admin.tenants.create'))
        ->assertSessionHasErrors('email');

    expect(Tenant::query()->where('email', 'ja-existe@example.com')->exists())->toBeFalse();
});

test('creating a tenant without a password never checks users table email uniqueness', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'conta-de-outro-papel@example.com']);

    $this->actingAs($admin)
        ->post(route('admin.tenants.store'), [
            'name' => 'Inquilino Sem Portal',
            'document' => '52998224725',
            'email' => 'conta-de-outro-papel@example.com',
            'whatsapp' => '5511999990000',
            'status' => 'active',
        ])
        ->assertRedirect(route('admin.tenants.index'))
        ->assertSessionDoesntHaveErrors();

    $tenant = Tenant::query()->where('email', 'conta-de-outro-papel@example.com')->first();

    expect($tenant)->not->toBeNull()
        ->and($tenant->user_id)->toBeNull();
});

test('admin can set a password for an already portal-linked tenant without a false unique conflict', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email' => 'inquilino-existente@example.com']);
    $user->assignRole(UserRole::Tenant);
    $tenant = Tenant::factory()->create(['email' => 'inquilino-existente@example.com', 'user_id' => $user->id]);

    $this->actingAs($admin)
        ->put(route('admin.tenants.update', $tenant), [
            'name' => $tenant->name,
            'document' => $tenant->document,
            'email' => 'inquilino-existente@example.com',
            'status' => $tenant->status->value,
            'password' => 'nova-senha-valida',
            'password_confirmation' => 'nova-senha-valida',
        ])
        ->assertRedirect(route('admin.tenants.index'))
        ->assertSessionDoesntHaveErrors();

    expect(Hash::check('nova-senha-valida', $user->fresh()->password))->toBeTrue();
});

test('admin cannot update a tenant to a portal email already used by another account', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'ja-existe-update@example.com']);
    $tenant = Tenant::factory()->create(['email' => 'sem-portal@example.com', 'user_id' => null]);

    $this->actingAs($admin)
        ->from(route('admin.tenants.edit', $tenant))
        ->put(route('admin.tenants.update', $tenant), [
            'name' => $tenant->name,
            'document' => $tenant->document,
            'email' => 'ja-existe-update@example.com',
            'status' => $tenant->status->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('admin.tenants.edit', $tenant))
        ->assertSessionHasErrors('email');

    expect($tenant->fresh()->user_id)->toBeNull();
});

test('deleting a tenant also deletes its portal login, so the email can be reused later', function () {
    $admin = User::factory()->admin()->create();
    // ->tenant() syncs roles down to exactly [Tenant] — a bare create()
    // would auto-assign Admin too (see UserFactory::configure()), making
    // the login "shared" and this test's exclusive-deletion assertion wrong.
    $user = User::factory()->tenant()->create(['email' => 'inquilino-removido@example.com']);
    $tenant = Tenant::factory()->create(['email' => 'inquilino-removido@example.com', 'user_id' => $user->id]);

    $this->actingAs($admin)
        ->delete(route('admin.tenants.destroy', $tenant))
        ->assertRedirect(route('admin.tenants.index'));

    expect(Tenant::query()->find($tenant->id))->toBeNull()
        ->and(User::query()->find($user->id))->toBeNull();

    // The email is free again — recreating a tenant with it must not 500.
    $this->actingAs($admin)
        ->post(route('admin.tenants.store'), [
            'name' => 'Inquilino Recriado',
            'document' => '52998224725',
            'email' => 'inquilino-removido@example.com',
            'whatsapp' => '5511999990000',
            'status' => 'active',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('admin.tenants.index'))
        ->assertSessionDoesntHaveErrors();
});

test('deleting a tenant without a portal account does not error', function () {
    $admin = User::factory()->admin()->create();
    $tenant = Tenant::factory()->create(['user_id' => null]);

    $this->actingAs($admin)
        ->delete(route('admin.tenants.destroy', $tenant))
        ->assertRedirect(route('admin.tenants.index'));

    expect(Tenant::query()->find($tenant->id))->toBeNull();
});

test('deleting a tenant never deletes a user still linked to a receiver', function () {
    $admin = User::factory()->admin()->create();
    // Single-role on purpose (see the comment on the earlier deletion test)
    // so what saves this login is unambiguously the Receiver row below.
    $user = User::factory()->tenant()->create();
    $tenant = Tenant::factory()->create(['user_id' => $user->id]);
    Receiver::factory()->create(['user_id' => $user->id]);

    $this->actingAs($admin)
        ->delete(route('admin.tenants.destroy', $tenant))
        ->assertRedirect(route('admin.tenants.index'));

    expect(User::query()->find($user->id))->not->toBeNull();
});

test('admin cannot delete a tenant that has an active contract', function () {
    $admin = User::factory()->admin()->create();
    $tenant = Tenant::factory()->create();
    Contract::factory()->active()->for($tenant)->create();

    $this->actingAs($admin)
        ->delete(route('admin.tenants.destroy', $tenant))
        ->assertRedirect();

    expect(Tenant::query()->find($tenant->id))->not->toBeNull();
});

test('admin cannot delete a tenant that has a contract expiring soon', function () {
    $admin = User::factory()->admin()->create();
    $tenant = Tenant::factory()->create();
    Contract::factory()->for($tenant)->create(['status' => ContractStatus::Expiring]);

    $this->actingAs($admin)
        ->delete(route('admin.tenants.destroy', $tenant))
        ->assertRedirect();

    expect(Tenant::query()->find($tenant->id))->not->toBeNull();
});

test('admin can delete a tenant whose only contract is a draft', function () {
    $admin = User::factory()->admin()->create();
    $tenant = Tenant::factory()->create();
    Contract::factory()->for($tenant)->create(['status' => ContractStatus::Draft]);

    $this->actingAs($admin)
        ->delete(route('admin.tenants.destroy', $tenant))
        ->assertRedirect(route('admin.tenants.index'));

    expect(Tenant::query()->find($tenant->id))->toBeNull();
});
