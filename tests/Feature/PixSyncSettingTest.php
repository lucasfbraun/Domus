<?php

use App\Enums\SyncIntervalUnit;
use App\Models\PixSyncSetting;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('non admin cannot access the settings page', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('admin.billing-settings.edit'))
        ->assertForbidden();
});

test('admin sees the default pix sync schedule when none was configured yet', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.billing-settings.edit'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/BillingSettings')
            ->where('pix_sync_enabled', true)
            ->where('pix_sync_interval_value', PixSyncSetting::DEFAULT_INTERVAL_VALUE)
            ->where('pix_sync_interval_unit', SyncIntervalUnit::Minutes->value)
            ->where('pix_sync_last_run_at', null));
});

test('admin can update the pix sync interval and unit', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.pix-sync-settings.update'), [
            'enabled' => '1',
            'interval_value' => 30,
            'interval_unit' => SyncIntervalUnit::Minutes->value,
        ])
        ->assertRedirect();

    $setting = PixSyncSetting::current();

    expect($setting->enabled)->toBeTrue()
        ->and($setting->interval_value)->toBe(30)
        ->and($setting->interval_unit)->toBe(SyncIntervalUnit::Minutes);
});

test('admin can switch the interval unit to hours', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.pix-sync-settings.update'), [
            'enabled' => '1',
            'interval_value' => 3,
            'interval_unit' => SyncIntervalUnit::Hours->value,
        ])
        ->assertRedirect();

    $setting = PixSyncSetting::current();

    expect($setting->interval_value)->toBe(3)
        ->and($setting->interval_unit)->toBe(SyncIntervalUnit::Hours);
});

test('unchecking the enabled checkbox actually disables the sync, not just leaves it untouched', function () {
    $admin = User::factory()->admin()->create();
    PixSyncSetting::current()->update(['enabled' => true]);

    // No `enabled` key at all — exactly what an unchecked HTML checkbox
    // submits. If the controller ever went back to a blind
    // $request->validated() spread, this would wrongly leave `enabled`
    // untouched instead of turning it off.
    $this->actingAs($admin)
        ->put(route('admin.pix-sync-settings.update'), [
            'interval_value' => 2,
            'interval_unit' => SyncIntervalUnit::Minutes->value,
        ])
        ->assertRedirect();

    expect(PixSyncSetting::current()->enabled)->toBeFalse();
});

test('non admin cannot update the pix sync schedule', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->put(route('admin.pix-sync-settings.update'), [
            'enabled' => '1',
            'interval_value' => 5,
            'interval_unit' => SyncIntervalUnit::Minutes->value,
        ])
        ->assertForbidden();
});

test('interval_unit must be a known value', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.pix-sync-settings.update'), [
            'enabled' => '1',
            'interval_value' => 5,
            'interval_unit' => 'days',
        ])
        ->assertSessionHasErrors('interval_unit');
});

test('interval_value must be within the allowed range', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.pix-sync-settings.update'), [
            'enabled' => '1',
            'interval_value' => 0,
            'interval_unit' => SyncIntervalUnit::Minutes->value,
        ])
        ->assertSessionHasErrors('interval_value');

    $this->actingAs($admin)
        ->put(route('admin.pix-sync-settings.update'), [
            'enabled' => '1',
            'interval_value' => PixSyncSetting::MAX_INTERVAL_VALUE + 1,
            'interval_unit' => SyncIntervalUnit::Minutes->value,
        ])
        ->assertSessionHasErrors('interval_value');
});
