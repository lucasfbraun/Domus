<?php

use App\Enums\UserRole;
use App\Models\Contract;
use App\Models\Owner;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('owner portal only sees own properties and contracts', function () {
    $ownerUser = User::factory()->owner()->create();
    $owner = Owner::factory()->create(['user_id' => $ownerUser->id]);
    $otherOwner = Owner::factory()->create();

    $ownProperty = Property::factory()->create();
    $owner->properties()->attach($ownProperty->id);

    $otherProperty = Property::factory()->create();
    $otherOwner->properties()->attach($otherProperty->id);

    $ownContract = Contract::factory()->active()->for($ownProperty)->create();
    Contract::factory()->active()->for($otherProperty)->create();

    $response = $this->actingAs($ownerUser)
        ->get(route('owner.portal'))
        ->assertSuccessful();

    $properties = collect($response->viewData('page')['props']['properties']['data']);
    $contracts = collect($response->viewData('page')['props']['contracts']['data']);

    expect($properties)->toHaveCount(1)
        ->and($properties->first()['id'])->toBe($ownProperty->id)
        ->and($contracts)->toHaveCount(1)
        ->and($contracts->first()['id'])->toBe($ownContract->id);
});

test('owner portal aggregates properties across every owner record linked to the same user', function () {
    $ownerUser = User::factory()->owner()->create();
    $ownerRecordA = Owner::factory()->create(['user_id' => $ownerUser->id]);
    $ownerRecordB = Owner::factory()->create(['user_id' => $ownerUser->id]);

    $propertyA = Property::factory()->create();
    $ownerRecordA->properties()->attach($propertyA->id);

    $propertyB = Property::factory()->create();
    $ownerRecordB->properties()->attach($propertyB->id);

    $response = $this->actingAs($ownerUser)
        ->get(route('owner.portal'))
        ->assertSuccessful();

    $properties = collect($response->viewData('page')['props']['properties']['data']);

    expect($properties->pluck('id')->sort()->values()->all())
        ->toBe(collect([$propertyA->id, $propertyB->id])->sort()->values()->all());
});

test('a user with no owner record cannot access the owner portal', function () {
    $userWithoutOwnerRecord = User::factory()->owner()->create();

    $this->actingAs($userWithoutOwnerRecord)
        ->get(route('owner.portal'))
        ->assertForbidden();
});

test('a tenant cannot access the owner portal', function () {
    $tenantUser = User::factory()->tenant()->create();

    $this->actingAs($tenantUser)
        ->get(route('owner.portal'))
        ->assertForbidden();
});

test('an admin who also holds the owner role can reach the owner portal directly', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(UserRole::Owner);
    $owner = Owner::factory()->create(['user_id' => $admin->id]);
    $property = Property::factory()->create();
    $owner->properties()->attach($property->id);

    $this->actingAs($admin)
        ->get(route('owner.portal'))
        ->assertSuccessful();
});
