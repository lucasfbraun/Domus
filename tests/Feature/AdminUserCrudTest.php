<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non admin cannot access the admins list', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('admin.admins.index'))
        ->assertForbidden();
});

test('admin sees the admins list', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.admins.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/admins/Index')
            ->has('admins.data', 1));
});

test('admin can create a new admin user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.admins.store'), [
            'name' => 'Novo Admin',
            'email' => 'novo-admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertRedirect(route('admin.admins.index'));

    $created = User::query()->where('email', 'novo-admin@example.com')->first();

    expect($created)->not->toBeNull()
        ->and($created->hasRole('admin'))->toBeTrue();
});

test('creating an admin with an email already in use fails validation', function () {
    $admin = User::factory()->admin()->create();
    $existing = User::factory()->admin()->create(['email' => 'ja-existe@example.com']);

    $this->actingAs($admin)
        ->from(route('admin.admins.create'))
        ->post(route('admin.admins.store'), [
            'name' => 'Outro',
            'email' => 'ja-existe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertRedirect(route('admin.admins.create'))
        ->assertSessionHasErrors('email');

    expect(User::query()->where('email', 'ja-existe@example.com')->count())->toBe(1);
});

test('admin can update another admins name and email', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.admins.update', $other), [
            'name' => 'Nome Atualizado',
            'email' => $other->email,
        ])
        ->assertRedirect(route('admin.admins.index'));

    expect($other->fresh()->name)->toBe('Nome Atualizado');
});

test('admin can change another admins password', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.admins.update', $other), [
            'name' => $other->name,
            'email' => $other->email,
            'password' => 'nova-senha-valida',
            'password_confirmation' => 'nova-senha-valida',
        ])
        ->assertRedirect();

    expect(Hash::check('nova-senha-valida', $other->fresh()->password))->toBeTrue();
});

test('updating an admin without a password does not touch the existing password', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();
    $originalHash = $other->password;

    $this->actingAs($admin)
        ->put(route('admin.admins.update', $other), [
            'name' => $other->name,
            'email' => $other->email,
        ])
        ->assertRedirect();

    expect($other->fresh()->password)->toBe($originalHash);
});

test('updating a non admin user through this endpoint 404s', function () {
    $admin = User::factory()->admin()->create();
    $tenantUser = User::factory()->tenant()->create();

    $this->actingAs($admin)
        ->put(route('admin.admins.update', $tenantUser), [
            'name' => 'x',
            'email' => $tenantUser->email,
        ])
        ->assertNotFound();
});

test('admin can delete another admin', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete(route('admin.admins.destroy', $other))
        ->assertRedirect(route('admin.admins.index'));

    expect(User::query()->find($other->id))->toBeNull();
});

test('an admin cannot delete their own account through this screen', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete(route('admin.admins.destroy', $admin))
        ->assertForbidden();

    expect(User::query()->find($admin->id))->not->toBeNull();
});

test('non admin cannot create, update or delete admins', function () {
    $tenantUser = User::factory()->tenant()->create();
    $other = User::factory()->admin()->create();

    $this->actingAs($tenantUser)
        ->post(route('admin.admins.store'), [
            'name' => 'x',
            'email' => 'y@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertForbidden();

    $this->actingAs($tenantUser)
        ->put(route('admin.admins.update', $other), ['name' => 'x', 'email' => $other->email])
        ->assertForbidden();

    $this->actingAs($tenantUser)
        ->delete(route('admin.admins.destroy', $other))
        ->assertForbidden();
});
