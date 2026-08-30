<?php

use App\Enums\ChargeStatus;
use App\Enums\ContractStatus;
use App\Models\BillingSetting;
use App\Models\Charge;
use App\Models\Contract;
use App\Services\BillingCycle;
use App\Services\ChargeScheduler;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Carbon;

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

test('monthly sweep does not create charges before the configured generation day', function () {
    // due_day=12 sits well inside the *old* hardcoded 5-day lead window
    // (today is 2 days before it) — this only stays uncreated if the new
    // generation-day gate (day 20) is actually being enforced.
    Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', 'America/Sao_Paulo'));
    BillingSetting::query()->create(['generation_day' => 20]);

    Contract::factory()->active()->create(['due_day' => 12, 'monthly_rent' => 1000]);

    $result = app(ChargeScheduler::class)->runMonthlyChargeSweep();

    expect($result['created'])->toBe(0)
        ->and(Charge::query()->count())->toBe(0);

    Carbon::setTestNow();
});

test('monthly sweep creates charges on the configured generation day even far from the due date', function () {
    // due_day=9 rolls over to next month's 9th (>10 days away) under
    // BillingCycle's own math, which the *old* 5-day lead window would have
    // skipped. Only the generation-day gate (day 20, reached today) creates it.
    Carbon::setTestNow(Carbon::parse('2026-08-20 12:00:00', 'America/Sao_Paulo'));
    BillingSetting::query()->create(['generation_day' => 20]);

    Contract::factory()->active()->create(['due_day' => 9, 'monthly_rent' => 1000]);

    $result = app(ChargeScheduler::class)->runMonthlyChargeSweep();

    expect($result['created'])->toBe(1)
        ->and(Charge::query()->count())->toBe(1);

    Carbon::setTestNow();
});

test('monthly sweep still catches up on charges days after the generation day has passed', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-25 12:00:00', 'America/Sao_Paulo'));
    BillingSetting::query()->create(['generation_day' => 20]);

    Contract::factory()->active()->create(['due_day' => 9, 'monthly_rent' => 1000]);

    $result = app(ChargeScheduler::class)->runMonthlyChargeSweep();

    expect($result['created'])->toBe(1);

    Carbon::setTestNow();
});

test('monthly sweep does not duplicate a charge already generated this cycle', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-20 12:00:00', 'America/Sao_Paulo'));
    BillingSetting::query()->create(['generation_day' => 20]);

    Contract::factory()->active()->create(['due_day' => 9, 'monthly_rent' => 1000]);

    app(ChargeScheduler::class)->runMonthlyChargeSweep();
    $second = app(ChargeScheduler::class)->runMonthlyChargeSweep();

    expect($second['created'])->toBe(0)
        ->and($second['skipped'])->toBe(1)
        ->and(Charge::query()->count())->toBe(1);

    Carbon::setTestNow();
});

test('monthly sweep marks overdue charges even before the generation day', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', 'America/Sao_Paulo'));
    BillingSetting::query()->create(['generation_day' => 20]);

    $contract = Contract::factory()->active()->create(['due_day' => 5, 'monthly_rent' => 1000]);
    $charge = Charge::factory()->for($contract)->create([
        'status' => ChargeStatus::Open,
        'due_date' => '2026-08-05',
    ]);

    app(ChargeScheduler::class)->runMonthlyChargeSweep();

    expect($charge->fresh()->status)->toBe(ChargeStatus::Overdue);

    Carbon::setTestNow();
});
