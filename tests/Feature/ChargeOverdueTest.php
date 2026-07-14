<?php

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\Contract;
use App\Services\ChargeScheduler;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('charge scheduler marks past-due open charges as overdue', function () {
    $contract = Contract::factory()->active()->create();

    $charge = Charge::factory()->for($contract)->create([
        'status' => ChargeStatus::Open,
        'due_date' => now('America/Sao_Paulo')->subDays(2)->toDateString(),
    ]);

    $updated = app(ChargeScheduler::class)->markOverdueCharges();

    expect($updated)->toBe(1)
        ->and($charge->fresh()->status)->toBe(ChargeStatus::Overdue);
});
