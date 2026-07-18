<?php

use App\Enums\ChargeStatus;
use App\Enums\ContractStatus;
use App\Enums\UserRole;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Receiver;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('demo seeder creates expected demo data', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(User::where('email', 'admin@example.com')->first())
        ->not->toBeNull()
        ->and(User::where('email', 'tenant@example.com')->first()?->tenant)
        ->not->toBeNull()
        ->and(User::where('email', 'receiver@example.com')->first()?->receiver)
        ->not->toBeNull()
        ->and(Owner::count())->toBe(1)
        ->and(Property::count())->toBe(2)
        ->and(Contract::where('status', ContractStatus::Active)->count())->toBe(1)
        ->and(Charge::count())->toBe(4)
        ->and(Charge::where('status', ChargeStatus::Paid)->count())->toBe(1)
        ->and(Charge::where('status', ChargeStatus::Overdue)->count())->toBe(1)
        ->and(Charge::where('status', ChargeStatus::Open)->count())->toBe(2);
});

test('user factory assigns admin role by default', function () {
    $user = User::factory()->create();

    expect($user->hasRole(UserRole::Admin))->toBeTrue();
});

test('user factory role states assign the expected role', function () {
    $tenantUser = User::factory()->tenant()->create();
    $receiverUser = User::factory()->receiver()->create();

    expect($tenantUser->hasRole(UserRole::Tenant))->toBeTrue()
        ->and($tenantUser->hasRole(UserRole::Admin))->toBeFalse()
        ->and($receiverUser->hasRole(UserRole::Receiver))->toBeTrue()
        ->and($receiverUser->hasRole(UserRole::Admin))->toBeFalse();
});

test('property manager models expose expected relationships', function () {
    $owner = Owner::factory()
        ->has(Property::factory()->count(2), 'properties')
        ->create();

    $contract = Contract::factory()
        ->for($owner->properties->first())
        ->active()
        ->create();

    Charge::factory()
        ->open()
        ->for($contract)
        ->for($contract->receiver)
        ->count(2)
        ->create();

    expect($owner->properties)->toHaveCount(2)
        ->and($contract->property->owners->pluck('id'))->toContain($owner->id)
        ->and($contract->charges)->toHaveCount(2)
        ->and($contract->tenant->user->hasRole(UserRole::Tenant))->toBeTrue();
});

test('receiver encrypts mercado pago tokens', function () {
    $receiver = Receiver::factory()->connected()->create();

    expect($receiver->mp_access_token)->not->toBeNull()
        ->and($receiver->getAttributes()['mp_access_token'])->not->toBe($receiver->mp_access_token);
});
