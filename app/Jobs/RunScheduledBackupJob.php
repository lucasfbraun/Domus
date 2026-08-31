<?php

namespace App\Jobs;

use App\Models\BackupSetting;
use App\Services\BackupScheduleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Scheduled hourly (see routes/console.php) — a no-op most ticks.
 * {@see BackupScheduleService::runIfDue()} checks the admin-configured
 * frequency/retention on {@see BackupSetting} and only
 * creates+prunes a backup when one is actually due. Distinct from
 * {@see CreateDatabaseBackupJob}, which unconditionally makes one backup
 * and is left as a manual/ad-hoc primitive rather than being scheduled
 * itself.
 */
class RunScheduledBackupJob implements ShouldQueue
{
    use Queueable;

    public function handle(BackupScheduleService $scheduler): void
    {
        $scheduler->runIfDue();
    }
}
