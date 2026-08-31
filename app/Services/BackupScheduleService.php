<?php

namespace App\Services;

use App\Enums\BackupFrequency;
use App\Jobs\RunScheduledBackupJob;
use App\Models\BackupSetting;
use Carbon\CarbonInterface;

/**
 * Decides whether an automatic backup is due right now and, if so, runs
 * one — see docs/adr/0012-configurable-backup-schedule.md for why this is
 * a due-check called periodically rather than a dynamically-built cron
 * expression. {@see RunScheduledBackupJob} calls
 * {@see runIfDue()} on an hourly schedule; the admin-configured
 * frequency/retention live on {@see BackupSetting}, not in code, so
 * changing them from Admin -> Configurações takes effect on the next tick
 * without a deploy.
 */
class BackupScheduleService
{
    public function __construct(
        private readonly DatabaseBackupService $backups,
    ) {}

    /**
     * @return bool whether a backup was actually created this call
     */
    public function runIfDue(): bool
    {
        $setting = BackupSetting::current();

        if ($setting->frequency === BackupFrequency::Disabled) {
            return false;
        }

        if (! $this->isDue($setting)) {
            return false;
        }

        $this->backups->create();
        $this->backups->prune($setting->retention_count);

        $setting->update(['last_run_at' => now()]);

        return true;
    }

    /**
     * When the next automatic backup will actually run, for display on
     * Admin -> Configurações — purely informational, {@see isDue()} is
     * still what decides in real time. Null when disabled.
     *
     * Setting an hour that has already passed today (whether this is the
     * very first run or the frequency's next due date lands on today)
     * doesn't run immediately — {@see isDue()} only ever fires during the
     * configured hour's window, so the next opportunity is that same hour
     * tomorrow. This exists so that isn't read as "nothing happened, is it
     * broken?" — the admin can see exactly when to expect it instead.
     */
    public function nextRunAt(): ?CarbonInterface
    {
        $setting = BackupSetting::current();

        if ($setting->frequency === BackupFrequency::Disabled) {
            return null;
        }

        $today = now()->startOfDay();

        $dueDate = $setting->last_run_at === null
            ? $today
            : $setting->frequency->nextDueAt($setting->last_run_at);

        if ($dueDate->lessThan($today)) {
            $dueDate = $today;
        }

        $candidate = $dueDate->setTime($setting->run_at_hour, 0);

        return $candidate->isPast() ? $candidate->addDay() : $candidate;
    }

    private function isDue(BackupSetting $setting): bool
    {
        // Gates on the hour first: RunScheduledBackupJob ticks every
        // hour, but a backup should only actually fire during the
        // admin-configured hour — otherwise a Daily schedule would run
        // at whatever hour happens to be current the moment the
        // interval elapses, drifting away from the chosen time.
        if (now()->hour !== $setting->run_at_hour) {
            return false;
        }

        if ($setting->last_run_at === null) {
            return true;
        }

        return now()->startOfDay()->greaterThanOrEqualTo($setting->frequency->nextDueAt($setting->last_run_at));
    }
}
