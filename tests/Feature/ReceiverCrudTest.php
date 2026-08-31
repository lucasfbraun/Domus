<?php

use App\Enums\UserRole;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin can create a receiver with portal password confirmation', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.receivers.store'), [
            'name' => 'Recebedor Portal',
            'document' => '52998224725',
            'email' => 'receiver-portal@example.com',
            'active' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('admin.receivers.index'))
        ->assertSessionDoesntHaveErrors();

    $receiver = Receiver::query()->where('email', 'receiver-portal@example.com')->first();

    expect($receiver)->not->toBeNull()
        ->and($receiver->user_id)->not->toBeNull();

    $user = User::query()->find($receiver->user_id);

    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('receiver-portal@example.com')
        ->and($user->hasRole(UserRole::Receiver))->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('admin cannot create a receiver with mismatched password confirmation', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.receivers.store'), [
            'name' => 'Recebedor Portal',
            'document' => '52998224725',
            'email' => 'receiver-portal@example.com',
            'active' => '1',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ])
        ->assertSessionHasErrors('password');

    expect(Receiver::query()->where('email', 'receiver-portal@example.com')->exists())->toBeFalse();
});

test('admin cannot create a receiver whose portal email is already used by another account', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'ja-existe@example.com']);

    $this->actingAs($admin)
        ->from(route('admin.receivers.create'))
        ->post(route('admin.receivers.store'), [
            'name' => 'Recebedor Duplicado',
            'document' => '52998224725',
            'email' => 'ja-existe@example.com',
            'active' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('admin.receivers.create'))
        ->assertSessionHasErrors('email');

    expect(Receiver::query()->where('email', 'ja-existe@example.com')->exists())->toBeFalse();
});

test('creating a receiver without a password never checks users table email uniqueness', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'conta-de-outro-papel@example.com']);

    $this->actingAs($admin)
        ->post(route('admin.receivers.store'), [
            'name' => 'Recebedor Sem Portal',
            'document' => '52998224725',
            'email' => 'conta-de-outro-papel@example.com',
            'active' => '1',
        ])
        ->assertRedirect(route('admin.receivers.index'))
        ->assertSessionDoesntHaveErrors();

    $receiver = Receiver::query()->where('email', 'conta-de-outro-papel@example.com')->first();

    expect($receiver)->not->toBeNull()
        ->and($receiver->user_id)->toBeNull();
});

test('admin can set a password for an already portal-linked receiver without a false unique conflict', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['email' => 'recebedor-existente@example.com']);
    $user->assignRole(UserRole::Receiver);
    $receiver = Receiver::factory()->create(['email' => 'recebedor-existente@example.com', 'user_id' => $user->id]);

    $this->actingAs($admin)
        ->put(route('admin.receivers.update', $receiver), [
            'name' => $receiver->name,
            'document' => $receiver->document,
            'email' => 'recebedor-existente@example.com',
            'active' => '1',
            'password' => 'nova-senha-valida',
            'password_confirmation' => 'nova-senha-valida',
        ])
        ->assertRedirect(route('admin.receivers.index'))
        ->assertSessionDoesntHaveErrors();

    expect(Hash::check('nova-senha-valida', $user->fresh()->password))->toBeTrue();
});

test('updating a receiver with a dedicated login syncs its name and email onto the login', function () {
    $admin = User::factory()->admin()->create();
    // ->receiver() syncs roles down to exactly [Receiver] — a bare create()
    // would also carry Admin (see UserFactory::configure()), making the
    // login look shared and hiding the very sync this test checks for.
    $user = User::factory()->receiver()->create(['name' => 'Nome Antigo', 'email' => 'recebedor-antigo@example.com']);
    $receiver = Receiver::factory()->create(['name' => 'Nome Antigo', 'email' => 'recebedor-antigo@example.com', 'user_id' => $user->id]);

    $this->actingAs($admin)
        ->put(route('admin.receivers.update', $receiver), [
            'name' => 'Nome Novo',
            'document' => $receiver->document,
            'email' => 'recebedor-novo@example.com',
            'active' => '1',
        ])
        ->assertRedirect(route('admin.receivers.index'))
        ->assertSessionDoesntHaveErrors();

    $fresh = $user->fresh();

    expect($fresh->name)->toBe('Nome Novo')
        ->and($fresh->email)->toBe('recebedor-novo@example.com');
});

test('updating a receiver whose login is shared with another role does not overwrite that login email', function () {
    $admin = User::factory()->admin()->create();
    $sharedUser = User::factory()->admin()->create(['email' => 'admin-compartilhado-recebedor@example.com']);
    $sharedUser->assignRole(UserRole::Receiver);
    $receiver = Receiver::factory()->create(['email' => 'recebedor-com-login-compartilhado@example.com', 'user_id' => $sharedUser->id]);

    $this->actingAs($admin)
        ->put(route('admin.receivers.update', $receiver), [
            'name' => $receiver->name,
            'document' => $receiver->document,
            'email' => 'novo-email-do-recebedor@example.com',
            'active' => '1',
        ])
        ->assertRedirect(route('admin.receivers.index'))
        ->assertSessionDoesntHaveErrors();

    expect($receiver->fresh()->email)->toBe('novo-email-do-recebedor@example.com')
        ->and($sharedUser->fresh()->email)->toBe('admin-compartilhado-recebedor@example.com');
});

test('admin cannot update a receiver to a portal email already used by another account', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'ja-existe-update@example.com']);
    $receiver = Receiver::factory()->create(['email' => 'sem-portal@example.com', 'user_id' => null]);

    $this->actingAs($admin)
        ->from(route('admin.receivers.edit', $receiver))
        ->put(route('admin.receivers.update', $receiver), [
            'name' => $receiver->name,
            'document' => $receiver->document,
            'email' => 'ja-existe-update@example.com',
            'active' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('admin.receivers.edit', $receiver))
        ->assertSessionHasErrors('email');

    expect($receiver->fresh()->user_id)->toBeNull();
});

test('deleting a receiver also deletes its portal login, so the email can be reused later', function () {
    $admin = User::factory()->admin()->create();
    // ->receiver() syncs roles down to exactly [Receiver] — a bare create()
    // would auto-assign Admin too (see UserFactory::configure()), making
    // the login "shared" and this test's exclusive-deletion assertion wrong.
    $user = User::factory()->receiver()->create(['email' => 'recebedor-removido@example.com']);
    $receiver = Receiver::factory()->create(['email' => 'recebedor-removido@example.com', 'user_id' => $user->id]);

    $this->actingAs($admin)
        ->delete(route('admin.receivers.destroy', $receiver))
        ->assertRedirect(route('admin.receivers.index'));

    expect(Receiver::query()->find($receiver->id))->toBeNull()
        ->and(User::query()->find($user->id))->toBeNull();

    // The email is free again — recreating a receiver with it must not 500.
    $this->actingAs($admin)
        ->post(route('admin.receivers.store'), [
            'name' => 'Recebedor Recriado',
            'document' => '52998224725',
            'email' => 'recebedor-removido@example.com',
            'active' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('admin.receivers.index'))
        ->assertSessionDoesntHaveErrors();
});

test('deleting a receiver without a portal account does not error', function () {
    $admin = User::factory()->admin()->create();
    $receiver = Receiver::factory()->create(['user_id' => null]);

    $this->actingAs($admin)
        ->delete(route('admin.receivers.destroy', $receiver))
        ->assertRedirect(route('admin.receivers.index'));

    expect(Receiver::query()->find($receiver->id))->toBeNull();
});

test('deleting a receiver never deletes a user still linked to a tenant', function () {
    $admin = User::factory()->admin()->create();
    // Single-role on purpose (see the comment on the earlier deletion test)
    // so what saves this login is unambiguously the Tenant row below.
    $user = User::factory()->receiver()->create();
    $receiver = Receiver::factory()->create(['user_id' => $user->id]);
    Tenant::factory()->create(['user_id' => $user->id]);

    $this->actingAs($admin)
        ->delete(route('admin.receivers.destroy', $receiver))
        ->assertRedirect(route('admin.receivers.index'));

    expect(User::query()->find($user->id))->not->toBeNull();
});
