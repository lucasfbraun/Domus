<?php

namespace App\Jobs;

use App\Services\DatabaseBackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Not scheduled by default — the backup feature is admin-triggered from
 * Admin -> Backups. Add `Schedule::job(new CreateDatabaseBackupJob)->daily();`
 * to routes/console.php if automatic daily backups are wanted; this job
 * exists so that's a one-line change instead of new code.
 */
class CreateDatabaseBackupJob implements ShouldQueue
{
    use Queueable;

    public function handle(DatabaseBackupService $backups): void
    {
        $backups->create();
    }
}
