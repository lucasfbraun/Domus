<?php

namespace App\Jobs;

use App\Services\ChargeScheduler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Scheduled daily at 09:00 (routes/console.php). Marks unpaid Charges past
 * their due date as Overdue, then generates next month's Charge for every
 * Active/Expiring Contract that doesn't have one yet, 5 days ahead of the
 * due date.
 */
class GenerateMonthlyChargesJob implements ShouldQueue
{
    use Queueable;

    public function handle(ChargeScheduler $scheduler): void
    {
        $scheduler->markOverdueCharges();
        $scheduler->runMonthlyChargeSweep();
    }
}
