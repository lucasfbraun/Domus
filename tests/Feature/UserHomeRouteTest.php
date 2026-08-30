<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('a plain admin lands on the dashboard', function () {
    $user = User::factory()->admin()->create();

    expect($user->homeRouteName())->toBe('dashboard');
});

test('a plain tenant lands on the tenant portal', function () {
    $user = User::factory()->tenant()->create();

    expect($user->homeRouteName())->toBe('tenant.portal');
});

test('a plain receiver lands on the receiver portal', function () {
    $user = User::factory()->receiver()->create();

    expect($user->homeRouteName())->toBe('receiver.portal');
});

test('a plain owner lands on the owner portal', function () {
    $user = User::factory()->owner()->create();

    expect($user->homeRouteName())->toBe('owner.portal');
});

test('admin wins over every other role the same user also holds', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(UserRole::Owner);
    $user->assignRole(UserRole::Receiver);
    $user->assignRole(UserRole::Tenant);

    expect($user->fresh()->homeRouteName())->toBe('dashboard');
});

test('owner wins over receiver and tenant when the user is not also an admin', function () {
    $user = User::factory()->owner()->create();
    $user->assignRole(UserRole::Receiver);
    $user->assignRole(UserRole::Tenant);

    expect($user->fresh()->homeRouteName())->toBe('owner.portal');
});

test('receiver wins over tenant when the user is neither admin nor owner', function () {
    $user = User::factory()->receiver()->create();
    $user->assignRole(UserRole::Tenant);

    expect($user->fresh()->homeRouteName())->toBe('receiver.portal');
});

test('a user with no role at all falls back to the dashboard', function () {
    $user = User::factory()->create();
    $user->syncRoles([]);

    expect($user->fresh()->homeRouteName())->toBe('dashboard');
});
