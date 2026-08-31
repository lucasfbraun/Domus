<?php

use App\Enums\BackupFrequency;
use App\Models\BackupSetting;
use App\Services\BackupScheduleService;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Storage;

test('disabled frequency never runs a backup', function () {
    Storage::fake('local');
    $this->travelTo('2026-01-01 03:00:00');
    BackupSetting::current()->update(['frequency' => BackupFrequency::Disabled, 'run_at_hour' => 3]);

    $ran = app(BackupScheduleService::class)->runIfDue();

    expect($ran)->toBeFalse();
    expect(app(DatabaseBackupService::class)->list())->toBeEmpty();
});

test('a schedule that has never run before waits for the configured hour, not the moment it is saved', function () {
    Storage::fake('local');
    BackupSetting::current()->update([
        'frequency' => BackupFrequency::Monthly,
        'run_at_hour' => 3,
        'last_run_at' => null,
    ]);

    $this->travelTo('2026-01-01 14:00:00');
    expect(app(BackupScheduleService::class)->runIfDue())->toBeFalse();
    expect(app(DatabaseBackupService::class)->list())->toBeEmpty();

    $this->travelTo('2026-01-02 03:00:00');
    expect(app(BackupScheduleService::class)->runIfDue())->toBeTrue();
    expect(app(DatabaseBackupService::class)->list())->toHaveCount(1);
    expect(BackupSetting::current()->last_run_at)->not->toBeNull();
});

test('never runs outside the configured hour, even once the interval has fully elapsed', function () {
    Storage::fake('local');
    $this->travelTo('2026-01-01 03:00:00');
    BackupSetting::current()->update([
        'frequency' => BackupFrequency::Daily,
        'run_at_hour' => 3,
        'last_run_at' => now(),
    ]);

    // A full day plus has passed, but it is not 03:00 right now.
    $this->travelTo('2026-01-03 14:00:00');
    expect(app(BackupScheduleService::class)->runIfDue())->toBeFalse();
    expect(app(DatabaseBackupService::class)->list())->toBeEmpty();
});

test('daily frequency is not due before its calendar day arrives, even at the right hour', function () {
    Storage::fake('local');
    $this->travelTo('2026-01-01 03:00:00');
    BackupSetting::current()->update([
        'frequency' => BackupFrequency::Daily,
        'run_at_hour' => 3,
        'last_run_at' => now(),
    ]);

    // Same day, same hour, e.g. the job ticking again — must not double-run.
    $ran = app(BackupScheduleService::class)->runIfDue();

    expect($ran)->toBeFalse();
    expect(app(DatabaseBackupService::class)->list())->toBeEmpty();
});

test('daily frequency runs the next day at the configured hour', function () {
    Storage::fake('local');
    $this->travelTo('2026-01-01 03:00:00');
    BackupSetting::current()->update([
        'frequency' => BackupFrequency::Daily,
        'run_at_hour' => 3,
        'last_run_at' => now(),
    ]);

    $this->travelTo('2026-01-02 03:00:00');
    $ran = app(BackupScheduleService::class)->runIfDue();

    expect($ran)->toBeTrue();
    expect(app(DatabaseBackupService::class)->list())->toHaveCount(1);
});

test('weekly frequency waits a full week', function () {
    Storage::fake('local');
    $this->travelTo('2026-01-01 03:00:00');
    BackupSetting::current()->update([
        'frequency' => BackupFrequency::Weekly,
        'run_at_hour' => 3,
        'last_run_at' => now(),
    ]);

    $this->travelTo('2026-01-07 03:00:00');
    expect(app(BackupScheduleService::class)->runIfDue())->toBeFalse();

    $this->travelTo('2026-01-08 03:00:00');
    expect(app(BackupScheduleService::class)->runIfDue())->toBeTrue();
});

test('monthly frequency from the 31st clamps instead of overflowing into the month after next', function () {
    // Same class of bug already found in DemoSeeder this session: a plain
    // addMonth() from Jan 31 skips past February entirely (Feb has no
    // 31st, so it lands in March). addMonthNoOverflow() must clamp to
    // Feb 28 instead.
    Storage::fake('local');
    $this->travelTo('2026-01-31 03:00:00');
    BackupSetting::current()->update([
        'frequency' => BackupFrequency::Monthly,
        'run_at_hour' => 3,
        'last_run_at' => now(),
    ]);

    $this->travelTo('2026-02-27 03:00:00');
    expect(app(BackupScheduleService::class)->runIfDue())->toBeFalse();

    $this->travelTo('2026-02-28 03:00:00');
    expect(app(BackupScheduleService::class)->runIfDue())->toBeTrue();
});

test('nextRunAt is null when disabled', function () {
    BackupSetting::current()->update(['frequency' => BackupFrequency::Disabled]);

    expect(app(BackupScheduleService::class)->nextRunAt())->toBeNull();
});

test('nextRunAt for a never-run schedule is today at the configured hour, if that hour has not passed yet', function () {
    $this->travelTo('2026-01-01 10:00:00');
    BackupSetting::current()->update([
        'frequency' => BackupFrequency::Daily,
        'run_at_hour' => 16,
        'last_run_at' => null,
    ]);

    expect(app(BackupScheduleService::class)->nextRunAt()->toDateTimeString())->toBe('2026-01-01 16:00:00');
});

test('nextRunAt for a never-run schedule rolls to tomorrow once the configured hour has already passed today', function () {
    // Mirrors the reported case: an admin configuring "16:00" at 18:00
    // must not be left thinking nothing happened — this is when the next
    // run actually lands.
    $this->travelTo('2026-01-01 18:00:00');
    BackupSetting::current()->update([
        'frequency' => BackupFrequency::Daily,
        'run_at_hour' => 16,
        'last_run_at' => null,
    ]);

    expect(app(BackupScheduleService::class)->nextRunAt()->toDateTimeString())->toBe('2026-01-02 16:00:00');
});

test('nextRunAt for a daily schedule already due today is today at the configured hour', function () {
    $this->travelTo('2026-01-02 10:00:00');
    BackupSetting::current()->update([
        'frequency' => BackupFrequency::Daily,
        'run_at_hour' => 16,
        'last_run_at' => '2026-01-01 16:00:00',
    ]);

    expect(app(BackupScheduleService::class)->nextRunAt()->toDateTimeString())->toBe('2026-01-02 16:00:00');
});

test('nextRunAt for a daily schedule that already ran today is tomorrow at the configured hour', function () {
    $this->travelTo('2026-01-01 16:30:00');
    BackupSetting::current()->update([
        'frequency' => BackupFrequency::Daily,
        'run_at_hour' => 16,
        'last_run_at' => '2026-01-01 16:00:00',
    ]);

    expect(app(BackupScheduleService::class)->nextRunAt()->toDateTimeString())->toBe('2026-01-02 16:00:00');
});

test('nextRunAt for a weekly schedule is the due calendar day at the configured hour', function () {
    $this->travelTo('2026-01-01 10:00:00');
    BackupSetting::current()->update([
        'frequency' => BackupFrequency::Weekly,
        'run_at_hour' => 3,
        'last_run_at' => '2026-01-01 03:00:00',
    ]);

    expect(app(BackupScheduleService::class)->nextRunAt()->toDateTimeString())->toBe('2026-01-08 03:00:00');
});

test('runIfDue prunes down to the configured retention count', function () {
    Storage::fake('local');
    $this->travelTo('2026-01-04 03:00:00');

    Storage::disk('local')->put('backups/domus-backup-2020-01-01_000000_000000.sql.gz', 'old-1');
    Storage::disk('local')->put('backups/domus-backup-2020-01-02_000000_000000.sql.gz', 'old-2');
    Storage::disk('local')->put('backups/domus-backup-2020-01-03_000000_000000.sql.gz', 'old-3');

    BackupSetting::current()->update([
        'frequency' => BackupFrequency::Daily,
        'run_at_hour' => 3,
        'retention_count' => 2,
        'last_run_at' => null,
    ]);

    app(BackupScheduleService::class)->runIfDue();

    // 3 pre-existing + 1 just created = 4, pruned down to 2 (the newest:
    // the one just created, plus 2020-01-03).
    $names = collect(app(DatabaseBackupService::class)->list())->pluck('name');

    expect($names)->toHaveCount(2)
        ->and($names)->toContain('domus-backup-2020-01-03_000000_000000.sql.gz')
        ->and($names)->not->toContain('domus-backup-2020-01-01_000000_000000.sql.gz')
        ->and($names)->not->toContain('domus-backup-2020-01-02_000000_000000.sql.gz');
});
