<?php

use App\Models\Owner;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin can list owners', function () {
    $admin = User::factory()->admin()->create();
    Owner::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get(route('admin.owners.index'))
        ->assertSuccessful();
});

test('admin can create an owner', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.owners.store'), [
            'name' => 'Joao Proprietario',
            'document' => '52998224725',
            'email' => 'joao@example.com',
            'phone' => '48999999999',
        ])
        ->assertRedirect(route('admin.owners.index'));

    $owner = Owner::query()->where('email', 'joao@example.com')->first();

    expect($owner)->not->toBeNull()
        ->and($owner->document)->toBe('52998224725')
        ->and($owner->phone)->toBe('5548999999999');
});

test('admin can update an owner', function () {
    $admin = User::factory()->admin()->create();
    $owner = Owner::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.owners.update', $owner), [
            'name' => 'Nome Atualizado',
            'document' => $owner->document,
            'email' => $owner->email,
            'phone' => $owner->phone,
        ])
        ->assertRedirect(route('admin.owners.index'));

    expect($owner->fresh()->name)->toBe('Nome Atualizado');
});

test('admin can delete an owner', function () {
    $admin = User::factory()->admin()->create();
    $owner = Owner::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.owners.destroy', $owner))
        ->assertRedirect(route('admin.owners.index'));

    expect(Owner::query()->find($owner->id))->toBeNull();
});
