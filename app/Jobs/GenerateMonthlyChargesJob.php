<?php

namespace App\Jobs;

use App\Models\BillingSetting;
use App\Services\ChargeScheduler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Scheduled daily at 09:00 (routes/console.php). Marks unpaid Charges past
 * their due date as Overdue, then — from the configured
 * {@see BillingSetting::$generation_day} onward each month —
 * generates the Charge for every Active/Expiring Contract that doesn't have
 * one yet for the current cycle. Each Contract's own due date is untouched;
 * only the day the Charge is created is governed by this setting.
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
