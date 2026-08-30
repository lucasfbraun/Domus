<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('a user flagged to change their password is redirected away from any other page', function () {
    $admin = User::factory()->admin()->create(['must_change_password' => true]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertRedirect(route('security.edit'));
});

test('a user flagged to change their password can still reach the security settings page', function () {
    $tenantUser = User::factory()->tenant()->create(['must_change_password' => true]);

    $this->actingAs($tenantUser)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertSuccessful();
});

test('a user flagged to change their password can still log out', function () {
    $tenantUser = User::factory()->tenant()->create(['must_change_password' => true]);

    $this->actingAs($tenantUser)
        ->post(route('logout'))
        ->assertRedirect();
});

test('a user not flagged is never redirected', function () {
    $admin = User::factory()->admin()->create(['must_change_password' => false]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertSuccessful();
});

test('updating the password clears the must change password flag', function () {
    $tenantUser = User::factory()->tenant()->create(['must_change_password' => true]);

    $this->actingAs($tenantUser)
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'uma-senha-bem-mais-longa-e-valida',
            'password_confirmation' => 'uma-senha-bem-mais-longa-e-valida',
        ])
        ->assertRedirect();

    expect($tenantUser->fresh()->must_change_password)->toBeFalse();
});

test('after changing the password, the user can reach other pages again', function () {
    $tenantUser = User::factory()->tenant()->create(['must_change_password' => true]);
    Tenant::factory()->create(['user_id' => $tenantUser->id]);

    $this->actingAs($tenantUser)
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'uma-senha-bem-mais-longa-e-valida',
            'password_confirmation' => 'uma-senha-bem-mais-longa-e-valida',
        ]);

    $this->actingAs($tenantUser->fresh())
        ->get(route('tenant.portal'))
        ->assertSuccessful();
});
