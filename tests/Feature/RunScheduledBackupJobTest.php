<?php

use App\Enums\BackupFrequency;
use App\Jobs\RunScheduledBackupJob;
use App\Models\BackupSetting;
use App\Services\BackupScheduleService;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Storage;

test('the job creates a backup when the schedule is due', function () {
    Storage::fake('local');
    $this->travelTo(now()->setTime(BackupSetting::DEFAULT_RUN_AT_HOUR, 0));
    BackupSetting::current()->update(['frequency' => BackupFrequency::Daily]);

    (new RunScheduledBackupJob)->handle(app(BackupScheduleService::class));

    expect(app(DatabaseBackupService::class)->list())->toHaveCount(1);
});

test('the job is a no-op when the schedule is disabled', function () {
    Storage::fake('local');
    $this->travelTo(now()->setTime(BackupSetting::DEFAULT_RUN_AT_HOUR, 0));
    BackupSetting::current()->update(['frequency' => BackupFrequency::Disabled]);

    (new RunScheduledBackupJob)->handle(app(BackupScheduleService::class));

    expect(app(DatabaseBackupService::class)->list())->toBeEmpty();
});

test('the job is a no-op outside the configured hour', function () {
    Storage::fake('local');
    $this->travelTo(now()->setTime((BackupSetting::DEFAULT_RUN_AT_HOUR + 6) % 24, 0));
    BackupSetting::current()->update(['frequency' => BackupFrequency::Daily]);

    (new RunScheduledBackupJob)->handle(app(BackupScheduleService::class));

    expect(app(DatabaseBackupService::class)->list())->toBeEmpty();
});
