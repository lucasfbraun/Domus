<?php

use App\Models\Charge;
use App\Models\Contract;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('tenant portal only sees own contracts and charges', function () {
    $tenantUser = User::factory()->tenant()->create();
    $tenant = Tenant::factory()->create(['user_id' => $tenantUser->id]);

    $otherTenant = Tenant::factory()->create();

    $ownContract = Contract::factory()->active()->for($tenant)->create();
    $otherContract = Contract::factory()->active()->for($otherTenant)->create();

    $ownCharge = Charge::factory()->open()->for($ownContract)->for($ownContract->receiver)->create();
    Charge::factory()->open()->for($otherContract)->for($otherContract->receiver)->create();

    $response = $this->actingAs($tenantUser)
        ->get(route('tenant.portal'))
        ->assertSuccessful();

    $contracts = collect($response->viewData('page')['props']['contracts']);
    $charges = collect($response->viewData('page')['props']['charges']);

    expect($contracts)->toHaveCount(1)
        ->and($contracts->first()['id'])->toBe($ownContract->id)
        ->and($charges)->toHaveCount(1)
        ->and($charges->first()['id'])->toBe($ownCharge->id);
});
