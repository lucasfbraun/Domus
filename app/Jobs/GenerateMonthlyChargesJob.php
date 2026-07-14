<?php

namespace App\Jobs;

use App\Services\ChargeScheduler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateMonthlyChargesJob implements ShouldQueue
{
    use Queueable;

    public function handle(ChargeScheduler $scheduler): void
    {
        $scheduler->markOverdueCharges();
        $scheduler->runMonthlyChargeSweep();
    }
}
