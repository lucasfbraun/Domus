<?php

use App\Models\Contract;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin can view any contract via the shared show page', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create();

    $this->actingAs($admin)
        ->get(route('contracts.show', $contract))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contracts/Show')
            ->where('contract.id', $contract->id)
            ->where('isAdmin', true)
            ->where('isTenant', false));
});

test('the owning tenant can view their own contract via the shared show page', function () {
    $tenantUser = User::factory()->tenant()->create();
    $tenant = Tenant::factory()->create(['user_id' => $tenantUser->id]);
    $contract = Contract::factory()->active()->for($tenant)->create();

    $this->actingAs($tenantUser)
        ->get(route('contracts.show', $contract))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contracts/Show')
            ->where('isTenant', true)
            ->where('isAdmin', false));
});

test('a tenant cannot view a contract that is not theirs', function () {
    $tenantUser = User::factory()->tenant()->create();
    Tenant::factory()->create(['user_id' => $tenantUser->id]);
    $otherContract = Contract::factory()->active()->create();

    $this->actingAs($tenantUser)
        ->get(route('contracts.show', $otherContract))
        ->assertForbidden();
});

test('the owning receiver can view a contract they receive payments for', function () {
    $receiverUser = User::factory()->receiver()->create();
    $receiver = Receiver::factory()->create(['user_id' => $receiverUser->id]);
    $contract = Contract::factory()->active()->for($receiver)->create();

    $this->actingAs($receiverUser)
        ->get(route('contracts.show', $contract))
        ->assertSuccessful();
});

test('a receiver cannot view a contract they do not receive payments for', function () {
    $receiverUser = User::factory()->receiver()->create();
    Receiver::factory()->create(['user_id' => $receiverUser->id]);
    $otherContract = Contract::factory()->active()->create();

    $this->actingAs($receiverUser)
        ->get(route('contracts.show', $otherContract))
        ->assertForbidden();
});
