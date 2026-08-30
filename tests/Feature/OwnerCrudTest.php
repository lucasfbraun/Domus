<?php

use App\Enums\UserRole;
use App\Models\Owner;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin can create an owner with no portal access', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.owners.store'), [
            'name' => 'Proprietario Sem Portal',
            'document' => '52998224725',
        ])
        ->assertRedirect(route('admin.owners.index'))
        ->assertSessionDoesntHaveErrors();

    $owner = Owner::query()->where('name', 'Proprietario Sem Portal')->firstOrFail();

    expect($owner->user_id)->toBeNull();
});

test('admin can create an owner with a brand new dedicated login', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.owners.store'), [
            'name' => 'Proprietario Portal',
            'document' => '52998224725',
            'email' => 'proprietario-portal@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('admin.owners.index'))
        ->assertSessionDoesntHaveErrors();

    $owner = Owner::query()->where('email', 'proprietario-portal@example.com')->firstOrFail();

    expect($owner->user_id)->not->toBeNull();

    $user = User::query()->findOrFail($owner->user_id);

    expect($user->hasRole(UserRole::Owner))->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('admin can create an owner linked to an existing user, gaining the owner role alongside their other roles', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.owners.store'), [
            'name' => 'Proprietario Vinculado',
            'document' => '52998224725',
            'existing_user_id' => $admin->id,
        ])
        ->assertRedirect(route('admin.owners.index'))
        ->assertSessionDoesntHaveErrors();

    $owner = Owner::query()->where('name', 'Proprietario Vinculado')->firstOrFail();

    expect($owner->user_id)->toBe($admin->id)
        ->and(User::query()->count())->toBe(1)
        ->and($admin->fresh()->hasRole(UserRole::Owner))->toBeTrue()
        ->and($admin->fresh()->hasRole(UserRole::Admin))->toBeTrue();
});

test('admin cannot submit both a password and an existing user id', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.owners.create'))
        ->post(route('admin.owners.store'), [
            'name' => 'Ambiguo',
            'document' => '52998224725',
            'existing_user_id' => $admin->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('admin.owners.create'))
        ->assertSessionHasErrors('password');

    expect(Owner::query()->where('name', 'Ambiguo')->exists())->toBeFalse();
});

test('admin cannot create an owner login with an email already used by another account', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'ja-existe@example.com']);

    $this->actingAs($admin)
        ->from(route('admin.owners.create'))
        ->post(route('admin.owners.store'), [
            'name' => 'Proprietario Duplicado',
            'document' => '52998224725',
            'email' => 'ja-existe@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('admin.owners.create'))
        ->assertSessionHasErrors('email');
});

test('admin can switch an owner from one linked user to another', function () {
    $admin = User::factory()->admin()->create();
    $oldUser = User::factory()->owner()->create();
    $newUser = User::factory()->receiver()->create();
    $owner = Owner::factory()->create(['user_id' => $oldUser->id]);

    $this->actingAs($admin)
        ->put(route('admin.owners.update', $owner), [
            'name' => $owner->name,
            'document' => $owner->document,
            'existing_user_id' => $newUser->id,
        ])
        ->assertRedirect(route('admin.owners.index'))
        ->assertSessionDoesntHaveErrors();

    expect($owner->fresh()->user_id)->toBe($newUser->id)
        ->and($newUser->fresh()->hasRole(UserRole::Owner))->toBeTrue()
        // Old user's only role was Owner, so losing it deletes that login.
        ->and(User::query()->find($oldUser->id))->toBeNull();
});

test('deleting an owner with a shared login only removes the owner role, keeping the login intact', function () {
    $admin = User::factory()->admin()->create();
    $owner = Owner::factory()->create(['user_id' => $admin->id]);
    $admin->assignRole(UserRole::Owner);

    $this->actingAs($admin)
        ->delete(route('admin.owners.destroy', $owner))
        ->assertRedirect(route('admin.owners.index'));

    expect(Owner::query()->find($owner->id))->toBeNull();

    $fresh = $admin->fresh();

    expect($fresh)->not->toBeNull()
        ->and($fresh->hasRole(UserRole::Admin))->toBeTrue()
        ->and($fresh->hasRole(UserRole::Owner))->toBeFalse();
});

test('deleting an owner with a dedicated login deletes that login too', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->owner()->create();
    $owner = Owner::factory()->create(['user_id' => $user->id]);

    $this->actingAs($admin)
        ->delete(route('admin.owners.destroy', $owner))
        ->assertRedirect(route('admin.owners.index'));

    expect(Owner::query()->find($owner->id))->toBeNull()
        ->and(User::query()->find($user->id))->toBeNull();
});
