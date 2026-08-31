<?php

use App\Enums\BackupFrequency;
use App\Models\BackupSetting;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('non admin cannot access the settings page', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('admin.billing-settings.edit'))
        ->assertForbidden();
});

test('admin sees the default backup schedule when none was configured yet', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.billing-settings.edit'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/BillingSettings')
            ->where('backup_frequency', BackupFrequency::Disabled->value)
            ->where('backup_retention_count', BackupSetting::DEFAULT_RETENTION_COUNT)
            ->where('backup_run_at_hour', BackupSetting::DEFAULT_RUN_AT_HOUR)
            ->where('backup_last_run_at', null));
});

test('admin can update the backup frequency, retention count and hour', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.backup-settings.update'), [
            'frequency' => BackupFrequency::Weekly->value,
            'retention_count' => 14,
            'run_at_hour' => 22,
        ])
        ->assertRedirect();

    $setting = BackupSetting::current();

    expect($setting->frequency)->toBe(BackupFrequency::Weekly)
        ->and($setting->retention_count)->toBe(14)
        ->and($setting->run_at_hour)->toBe(22);
});

test('non admin cannot update the backup schedule', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->put(route('admin.backup-settings.update'), [
            'frequency' => BackupFrequency::Daily->value,
            'retention_count' => 5,
            'run_at_hour' => 3,
        ])
        ->assertForbidden();
});

test('frequency must be a known value', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.backup-settings.update'), [
            'frequency' => 'yearly',
            'retention_count' => 5,
            'run_at_hour' => 3,
        ])
        ->assertSessionHasErrors('frequency');
});

test('retention count must be within the allowed range', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.backup-settings.update'), [
            'frequency' => BackupFrequency::Daily->value,
            'retention_count' => 0,
            'run_at_hour' => 3,
        ])
        ->assertSessionHasErrors('retention_count');

    $this->actingAs($admin)
        ->put(route('admin.backup-settings.update'), [
            'frequency' => BackupFrequency::Daily->value,
            'retention_count' => BackupSetting::MAX_RETENTION_COUNT + 1,
            'run_at_hour' => 3,
        ])
        ->assertSessionHasErrors('retention_count');
});

test('run_at_hour must be within 0 and 23', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.backup-settings.update'), [
            'frequency' => BackupFrequency::Daily->value,
            'retention_count' => 5,
            'run_at_hour' => -1,
        ])
        ->assertSessionHasErrors('run_at_hour');

    $this->actingAs($admin)
        ->put(route('admin.backup-settings.update'), [
            'frequency' => BackupFrequency::Daily->value,
            'retention_count' => 5,
            'run_at_hour' => 24,
        ])
        ->assertSessionHasErrors('run_at_hour');
});
