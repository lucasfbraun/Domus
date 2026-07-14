<?php

use App\Jobs\GenerateMonthlyChargesJob;
use App\Jobs\RunReminderSweepJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new GenerateMonthlyChargesJob)->dailyAt('09:00')->timezone('America/Sao_Paulo');
Schedule::job(new RunReminderSweepJob)->dailyAt('10:00')->timezone('America/Sao_Paulo');
