<?php

use App\Enums\UserRole;
use App\Models\Owner;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('backfills a dedicated tenant login whose email went stale before the sync fix existed', function () {
    // ->tenant() syncs roles down to exactly [Tenant] — see the comment on
    // similar setups in TenantCrudTest for why a bare create() would not do.
    $user = User::factory()->tenant()->create(['name' => 'Nome Antigo', 'email' => 'email-antigo@example.com']);
    $tenant = Tenant::factory()->create(['name' => 'Nome Novo', 'email' => 'email-novo@example.com', 'user_id' => $user->id]);

    $this->artisan('portal-accounts:sync-logins')->assertExitCode(0);

    $fresh = $user->fresh();

    expect($fresh->name)->toBe('Nome Novo')
        ->and($fresh->email)->toBe('email-novo@example.com');
});

test('backfills receiver and owner logins the same way', function () {
    $receiverUser = User::factory()->receiver()->create(['email' => 'receiver-antigo@example.com']);
    $receiver = Receiver::factory()->create(['email' => 'receiver-novo@example.com', 'user_id' => $receiverUser->id]);

    $ownerUser = User::factory()->owner()->create(['email' => 'owner-antigo@example.com']);
    $owner = Owner::factory()->create(['email' => 'owner-novo@example.com', 'user_id' => $ownerUser->id]);

    $this->artisan('portal-accounts:sync-logins')->assertExitCode(0);

    expect($receiverUser->fresh()->email)->toBe('receiver-novo@example.com')
        ->and($ownerUser->fresh()->email)->toBe('owner-novo@example.com');

    expect(Receiver::query()->find($receiver->id))->not->toBeNull()
        ->and(Owner::query()->find($owner->id))->not->toBeNull();
});

test('does not touch a login shared with another role', function () {
    $sharedUser = User::factory()->admin()->create(['email' => 'admin-compartilhado@example.com']);
    $sharedUser->assignRole(UserRole::Owner);
    Owner::factory()->create(['email' => 'proprietario-novo@example.com', 'user_id' => $sharedUser->id]);

    $this->artisan('portal-accounts:sync-logins')->assertExitCode(0);

    expect($sharedUser->fresh()->email)->toBe('admin-compartilhado@example.com');
});

test('is a no-op when nothing is actually out of sync', function () {
    $user = User::factory()->tenant()->create(['name' => 'Ja Sincronizado', 'email' => 'ja-sincronizado@example.com']);
    Tenant::factory()->create(['name' => 'Ja Sincronizado', 'email' => 'ja-sincronizado@example.com', 'user_id' => $user->id]);

    $this->artisan('portal-accounts:sync-logins')
        ->expectsOutputToContain('0')
        ->assertExitCode(0);
});

test('skips a tenant with no linked user without error', function () {
    $tenant = Tenant::factory()->create(['user_id' => null]);

    $this->artisan('portal-accounts:sync-logins')->assertExitCode(0);

    expect($tenant->fresh())->not->toBeNull();
});
