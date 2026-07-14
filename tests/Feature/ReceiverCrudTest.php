<?php

use App\Enums\UserRole;
use App\Models\Receiver;
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
