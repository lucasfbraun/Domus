<?php

use App\Enums\SyncIntervalUnit;
use App\Models\PixSyncSetting;
use App\Services\PixSyncScheduleService;

test('a disabled setting is never due', function () {
    PixSyncSetting::current()->update(['enabled' => false, 'last_run_at' => null]);

    expect(app(PixSyncScheduleService::class)->isDue())->toBeFalse();
});

test('a setting that has never run before is due immediately', function () {
    PixSyncSetting::current()->update(['enabled' => true, 'last_run_at' => null]);

    expect(app(PixSyncScheduleService::class)->isDue())->toBeTrue();
});

test('is not due before the configured minute interval elapses', function () {
    $this->travelTo('2026-01-01 12:00:00');
    PixSyncSetting::current()->update([
        'enabled' => true,
        'interval_value' => 5,
        'interval_unit' => SyncIntervalUnit::Minutes,
        'last_run_at' => now(),
    ]);

    $this->travelTo('2026-01-01 12:04:59');
    expect(app(PixSyncScheduleService::class)->isDue())->toBeFalse();
});

test('is due once the configured minute interval elapses', function () {
    $this->travelTo('2026-01-01 12:00:00');
    PixSyncSetting::current()->update([
        'enabled' => true,
        'interval_value' => 5,
        'interval_unit' => SyncIntervalUnit::Minutes,
        'last_run_at' => now(),
    ]);

    $this->travelTo('2026-01-01 12:05:00');
    expect(app(PixSyncScheduleService::class)->isDue())->toBeTrue();
});

test('an hour interval is converted to minutes before comparing', function () {
    $this->travelTo('2026-01-01 12:00:00');
    PixSyncSetting::current()->update([
        'enabled' => true,
        'interval_value' => 2,
        'interval_unit' => SyncIntervalUnit::Hours,
        'last_run_at' => now(),
    ]);

    $this->travelTo('2026-01-01 13:59:59');
    expect(app(PixSyncScheduleService::class)->isDue())->toBeFalse();

    $this->travelTo('2026-01-01 14:00:00');
    expect(app(PixSyncScheduleService::class)->isDue())->toBeTrue();
});

test('markRan stamps last_run_at with now', function () {
    $this->travelTo('2026-01-01 12:00:00');
    PixSyncSetting::current()->update(['last_run_at' => null]);

    app(PixSyncScheduleService::class)->markRan();

    expect(PixSyncSetting::current()->last_run_at)->not->toBeNull()
        ->and(PixSyncSetting::current()->last_run_at->equalTo(now()))->toBeTrue();
});
