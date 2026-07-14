<?php

use App\Enums\ChargeStatus;
use App\Enums\ContractStatus;
use App\Models\Charge;
use App\Models\Contract;
use App\Services\BillingCycle;
use App\Services\ChargeScheduler;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('charge scheduler generates charge for active contract', function () {
    $contract = Contract::factory()->active()->create([
        'due_day' => 10,
        'monthly_rent' => 1500,
        'status' => ContractStatus::Active,
    ]);

    $today = BillingCycle::todayInSaoPaulo();
    $cycle = BillingCycle::resolveBillingCycleDueDate($contract->due_day, $today);
    $reference = BillingCycle::formatReference($cycle['dueDateIso']);

    $scheduler = app(ChargeScheduler::class);
    $result = $scheduler->generateChargeForContract($contract);

    expect($result['created'])->toBeTrue()
        ->and($result['reference'])->toBe($reference);

    $charge = Charge::query()->where('contract_id', $contract->id)->where('reference', $reference)->first();

    expect($charge)->not->toBeNull()
        ->and((float) $charge->original_amount)->toBe(1500.0)
        ->and($charge->status)->toBe(ChargeStatus::Open);
});

test('charge scheduler does not duplicate paid charge reference', function () {
    $contract = Contract::factory()->active()->create(['monthly_rent' => 1200]);

    $scheduler = app(ChargeScheduler::class);
    $first = $scheduler->generateChargeForContract($contract);

    Charge::query()->whereKey($first['chargeId'])->update(['status' => ChargeStatus::Paid]);

    $second = $scheduler->generateChargeForContract($contract);

    expect($second['created'])->toBeFalse()
        ->and($second['updated'])->toBeFalse();
});
