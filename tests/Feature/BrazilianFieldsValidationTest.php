<?php

use App\Models\Owner;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('owner phone and document are normalized and validated on create', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.owners.store'), [
            'name' => 'Joao Proprietario',
            'document' => '529.982.247-25',
            'email' => 'joao@example.com',
            'phone' => '(48) 99999-9999',
        ])
        ->assertRedirect(route('admin.owners.index'));

    $owner = Owner::query()->where('email', 'joao@example.com')->first();

    expect($owner)->not->toBeNull()
        ->and($owner->document)->toBe('52998224725')
        ->and($owner->phone)->toBe('5548999999999');
});

test('owner create rejects invalid document', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.owners.store'), [
            'name' => 'Joao Proprietario',
            'document' => '12345678901',
            'email' => 'joao@example.com',
            'phone' => '48999999999',
        ])
        ->assertSessionHasErrors('document');
});

test('tenant whatsapp is stored with ddi 55 and digits only', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.tenants.store'), [
            'name' => 'Maria Inquilina',
            'document' => '11.222.333/0001-81',
            'email' => 'maria@example.com',
            'whatsapp' => '11 98888-7777',
            'status' => 'active',
            'resident_count' => 1,
        ])
        ->assertRedirect(route('admin.tenants.index'));

    $tenant = Tenant::query()->where('email', 'maria@example.com')->first();

    expect($tenant)->not->toBeNull()
        ->and($tenant->document)->toBe('11222333000181')
        ->and($tenant->whatsapp)->toBe('5511988887777');
});

test('receiver document accepts valid cnpj without mask', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.receivers.store'), [
            'name' => 'Recebedor SA',
            'document' => '11222333000181',
            'email' => 'receiver@example.com',
            'active' => '1',
        ])
        ->assertRedirect(route('admin.receivers.index'));

    expect(Receiver::query()->where('email', 'receiver@example.com')->value('document'))
        ->toBe('11222333000181');
});
