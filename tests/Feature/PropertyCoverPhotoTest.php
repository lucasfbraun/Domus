<?php

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\Owner;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');
});

test('admin can create a property with a cover photo', function () {
    $admin = User::factory()->admin()->create();
    $owner = Owner::factory()->create();
    $photo = UploadedFile::fake()->image('capa.jpg', 640, 480);

    $this->actingAs($admin)
        ->post(route('admin.properties.store'), [
            'name' => 'Apartamento com foto',
            'address' => 'Rua das Flores, 100',
            'type' => PropertyType::Apartment->value,
            'status' => PropertyStatus::Available->value,
            'owner_ids' => [$owner->id],
            'photo' => $photo,
        ])
        ->assertRedirect(route('admin.properties.index'));

    $property = Property::query()->where('name', 'Apartamento com foto')->first();

    expect($property)->not->toBeNull()
        ->and($property->getFirstMedia(Property::COVER_COLLECTION))->not->toBeNull()
        ->and($property->coverUrl())->not->toBeNull();
});

test('admin can replace and remove a property cover photo', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();
    $property
        ->addMedia(UploadedFile::fake()->image('antiga.jpg', 200, 200))
        ->toMediaCollection(Property::COVER_COLLECTION);

    expect($property->getMedia(Property::COVER_COLLECTION))->toHaveCount(1);

    $this->actingAs($admin)
        ->put(route('admin.properties.update', $property), [
            'name' => $property->name,
            'address' => $property->address,
            'type' => $property->type->value,
            'status' => $property->status->value,
            'photo' => UploadedFile::fake()->image('nova.jpg', 320, 240),
        ])
        ->assertRedirect(route('admin.properties.index'));

    $property->refresh();

    expect($property->getMedia(Property::COVER_COLLECTION))->toHaveCount(1)
        ->and($property->getFirstMedia(Property::COVER_COLLECTION)?->file_name)->toContain('nova');

    $this->actingAs($admin)
        ->put(route('admin.properties.update', $property), [
            'name' => $property->name,
            'address' => $property->address,
            'type' => $property->type->value,
            'status' => $property->status->value,
            'remove_photo' => true,
        ])
        ->assertRedirect(route('admin.properties.index'));

    $property->refresh();

    expect($property->getMedia(Property::COVER_COLLECTION))->toHaveCount(0)
        ->and($property->coverUrl())->toBeNull();
});

test('properties index includes cover url and type label', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create([
        'type' => PropertyType::House,
        'name' => 'Casa Vista Mar',
    ]);
    $property
        ->addMedia(UploadedFile::fake()->image('casa.jpg', 400, 300))
        ->toMediaCollection(Property::COVER_COLLECTION);

    $this->actingAs($admin)
        ->get(route('admin.properties.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/properties/Index')
            ->has('properties.data', 1)
            ->where('properties.data.0.name', 'Casa Vista Mar')
            ->where('properties.data.0.type_label', 'Casa')
            ->where('properties.data.0.cover_url', fn ($url) => is_string($url) && $url !== ''));
});

test('properties index returns null cover url when property has no photo', function () {
    $admin = User::factory()->admin()->create();
    Property::factory()->create([
        'name' => 'Sem foto',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.properties.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/properties/Index')
            ->has('properties.data', 1)
            ->where('properties.data.0.name', 'Sem foto')
            ->where('properties.data.0.cover_url', null));
});
