<?php

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\Owner;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('property form includes type options from the enum', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.properties.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/properties/Form')
            ->has('types', count(PropertyType::cases()))
            ->where('types.0.value', PropertyType::Apartment->value)
            ->where('types.0.label', 'Apartamento'));
});

test('admin can create a property with a valid type', function () {
    $admin = User::factory()->admin()->create();
    $owner = Owner::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.properties.store'), [
            'name' => 'Apartamento Centro',
            'address' => 'Rua das Flores, 100',
            'type' => PropertyType::Apartment->value,
            'status' => PropertyStatus::Available->value,
            'owner_id' => $owner->id,
        ])
        ->assertRedirect(route('admin.properties.index'));

    $property = Property::query()->where('name', 'Apartamento Centro')->first();

    expect($property)->not->toBeNull()
        ->and($property->type)->toBe(PropertyType::Apartment);
});

test('admin cannot create a property with an invalid type', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.properties.store'), [
            'name' => 'Imovel Invalido',
            'address' => 'Rua Teste, 1',
            'type' => 'chalet',
            'status' => PropertyStatus::Available->value,
        ])
        ->assertSessionHasErrors('type');
});
