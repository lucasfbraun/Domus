<?php

namespace App\Jobs;

use App\Services\ReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Scheduled daily at 10:00 (routes/console.php); sends due/overdue Charge reminders. */
class RunReminderSweepJob implements ShouldQueue
{
    use Queueable;

    public function handle(ReminderService $reminderService): void
    {
        $reminderService->runReminderSweep();
    }
}
