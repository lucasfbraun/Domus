<?php

use App\Enums\UserRole;
use App\Models\Owner;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PortalAccountService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('attach creates a brand new user when no existing user id is given', function () {
    $userId = app(PortalAccountService::class)->attach(UserRole::Receiver, [
        'name' => 'Novo Recebedor',
        'email' => 'novo-recebedor@example.com',
        'password' => 'password',
    ], null);

    $user = User::query()->findOrFail($userId);

    expect($user->name)->toBe('Novo Recebedor')
        ->and($user->email)->toBe('novo-recebedor@example.com')
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->hasRole(UserRole::Receiver))->toBeTrue()
        // No self-registration flow exists for this role, so nothing ever
        // sends a verification email — leaving this null would permanently
        // lock the account out behind the global `verified` middleware.
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

test('attach adds the role to an existing user instead of creating a new one', function () {
    $admin = User::factory()->admin()->create();

    $userId = app(PortalAccountService::class)->attach(UserRole::Owner, [
        'name' => 'ignored',
        'email' => 'ignored@example.com',
        'password' => 'ignored',
    ], $admin->id);

    expect($userId)->toBe($admin->id)
        ->and(User::query()->count())->toBe(1)
        ->and($admin->fresh()->hasRole(UserRole::Owner))->toBeTrue()
        ->and($admin->fresh()->hasRole(UserRole::Admin))->toBeTrue();
});

test('attach to an existing user that already has the role is a no-op', function () {
    $user = User::factory()->owner()->create();

    app(PortalAccountService::class)->attach(UserRole::Owner, [
        'name' => 'x', 'email' => 'x@example.com', 'password' => 'x',
    ], $user->id);

    expect($user->fresh()->roles()->where('name', UserRole::Owner->value)->count())->toBe(1);
});

test('detach deletes the user when the role was its only purpose', function () {
    // ->receiver() syncs roles down to exactly [Receiver] — a bare
    // factory create() would auto-assign Admin too (see UserFactory::configure()),
    // which would wrongly make this user look "shared" for the check below.
    $user = User::factory()->receiver()->create();

    app(PortalAccountService::class)->detach($user->id, UserRole::Receiver);

    expect(User::query()->find($user->id))->toBeNull();
});

test('detach only strips the role when the user still has another role', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(UserRole::Receiver);

    app(PortalAccountService::class)->detach($user->id, UserRole::Receiver);

    $fresh = $user->fresh();

    expect($fresh)->not->toBeNull()
        ->and($fresh->hasRole(UserRole::Receiver))->toBeFalse()
        ->and($fresh->hasRole(UserRole::Admin))->toBeTrue();
});

test('detach only strips the role when another domain record still points at the user', function () {
    // Single-role (Owner-only) on purpose, so the thing that saves this
    // login from deletion is unambiguously the other Owner row below, not
    // an extra role.
    $user = User::factory()->owner()->create();
    Owner::factory()->create(['user_id' => $user->id]);

    app(PortalAccountService::class)->detach($user->id, UserRole::Owner);

    expect(User::query()->find($user->id))->not->toBeNull();
});

test('detach is a safe no-op for a user id that does not exist', function () {
    app(PortalAccountService::class)->detach(999999, UserRole::Tenant);
})->throwsNoExceptions();

test('detach still deletes the user once no record references it any more', function () {
    $user = User::factory()->receiver()->create();
    $receiver = Receiver::factory()->create(['user_id' => $user->id]);

    // Caller's real sequence: delete/unlink the owning record first...
    $receiver->update(['user_id' => null]);

    // ...then detach. No other record references this user any more, so it
    // should be deleted as an exclusive login.
    app(PortalAccountService::class)->detach($user->id, UserRole::Receiver);

    expect(User::query()->find($user->id))->toBeNull();
});

test('sync creates a dedicated login when there is no current user and a password is given', function () {
    $userId = app(PortalAccountService::class)->sync(
        UserRole::Owner, null, null, 'Novo Proprietario', 'novo-proprietario@example.com', 'password',
    );

    $user = User::query()->findOrFail($userId);

    expect($user->email)->toBe('novo-proprietario@example.com')
        ->and($user->hasRole(UserRole::Owner))->toBeTrue();
});

test('sync links to a different existing user, leaving the previous one untouched', function () {
    $oldUser = User::factory()->owner()->create();
    $newUser = User::factory()->admin()->create();

    $userId = app(PortalAccountService::class)->sync(
        UserRole::Owner, $oldUser->id, $newUser->id, null, null, null,
    );

    expect($userId)->toBe($newUser->id)
        ->and($newUser->fresh()->hasRole(UserRole::Owner))->toBeTrue()
        // sync() never detaches the old user itself — doing so here, before
        // the caller has persisted the new user_id on its own record, would
        // find that record still pointing at $oldUser and wrongly treat the
        // login as still in use. Detaching is the caller's job, done in the
        // right order (see the docblock on PortalAccountService::sync()).
        ->and($oldUser->fresh()->hasRole(UserRole::Owner))->toBeTrue();
});

test('the documented sync-then-persist-then-detach sequence correctly frees an exclusive old login', function () {
    $service = app(PortalAccountService::class);
    $oldUser = User::factory()->owner()->create();
    $newUser = User::factory()->admin()->create();
    $owner = Owner::factory()->create(['user_id' => $oldUser->id]);

    $oldUserId = $owner->user_id;
    $userId = $service->sync(UserRole::Owner, $oldUserId, $newUser->id, null, null, null);

    expect($userId)->not->toBe($oldUserId);

    $owner->update(['user_id' => $userId]);
    $service->detach($oldUserId, UserRole::Owner);

    expect(User::query()->find($oldUserId))->toBeNull()
        ->and($owner->fresh()->user_id)->toBe($newUser->id);
});

test('sync with a password and an existing linked user just updates that password', function () {
    $user = User::factory()->owner()->create();
    $originalEmail = $user->email;

    $userId = app(PortalAccountService::class)->sync(
        UserRole::Owner, $user->id, null, 'ignored', 'ignored@example.com', 'nova-senha-valida',
    );

    $fresh = $user->fresh();

    expect($userId)->toBe($user->id)
        ->and($fresh->email)->toBe($originalEmail)
        ->and(Hash::check('nova-senha-valida', $fresh->password))->toBeTrue();
});

test('sync with nothing given leaves the current user id untouched', function () {
    $user = User::factory()->owner()->create();

    $userId = app(PortalAccountService::class)->sync(UserRole::Owner, $user->id, null, null, null, null);

    expect($userId)->toBe($user->id);
});

test('sync with nothing given and no current user stays null', function () {
    $userId = app(PortalAccountService::class)->sync(UserRole::Owner, null, null, null, null, null);

    expect($userId)->toBeNull();
});

test('detach does not delete a login that a tenant record still uses', function () {
    $user = User::factory()->receiver()->create();
    Tenant::factory()->create(['user_id' => $user->id]);

    app(PortalAccountService::class)->detach($user->id, UserRole::Receiver);

    expect(User::query()->find($user->id))->not->toBeNull();
});
