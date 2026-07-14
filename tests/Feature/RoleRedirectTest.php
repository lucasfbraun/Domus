<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin users authenticate to the dashboard', function () {
    $user = User::factory()->admin()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('tenant users authenticate to the tenant portal', function () {
    $user = User::factory()->tenant()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('tenant.portal', absolute: false));
});

test('receiver users authenticate to the receiver portal', function () {
    $user = User::factory()->receiver()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('receiver.portal', absolute: false));
});

test('home redirects guests to login', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('home redirects authenticated users by role', function (string $factoryState, string $routeName) {
    $user = User::factory()->{$factoryState}()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route($routeName));
})->with([
    'admin' => ['admin', 'dashboard'],
    'tenant' => ['tenant', 'tenant.portal'],
    'receiver' => ['receiver', 'receiver.portal'],
]);

test('registration is disabled', function () {
    $this->get('/register')->assertNotFound();
});

test('roles exist after seeding', function () {
    $roleNames = array_map(fn (UserRole $role) => $role->value, UserRole::cases());

    expect(Role::whereIn('name', $roleNames)->count())->toBe(count($roleNames));
});
