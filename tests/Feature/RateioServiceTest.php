<?php

use App\Enums\ChargeStatus;
use App\Enums\RateioSplitMode;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\Property;
use App\Models\RateioAllocation;
use App\Models\Tenant;
use App\Services\BillingCycle;
use App\Services\RateioService;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('rateio split applies to existing charge', function () {
    $propertyA = Property::factory()->create();
    $propertyB = Property::factory()->create();

    $tenantA = Tenant::factory()->create(['resident_count' => 2]);
    $tenantB = Tenant::factory()->create(['resident_count' => 1]);

    $contractA = Contract::factory()->active()->for($propertyA)->for($tenantA)->create(['monthly_rent' => 1000]);
    $contractB = Contract::factory()->active()->for($propertyB)->for($tenantB)->create(['monthly_rent' => 1000]);

    $today = BillingCycle::todayInSaoPaulo();
    $reference = BillingCycle::formatReference(
        BillingCycle::resolveBillingCycleDueDate($contractA->due_day, $today)['dueDateIso'],
    );

    $chargeA = Charge::factory()->open()->for($contractA)->for($contractA->receiver)->create([
        'reference' => $reference,
        'original_amount' => 1000,
        'due_date' => $today,
    ]);

    Charge::factory()->open()->for($contractB)->for($contractB->receiver)->create([
        'reference' => $reference,
        'original_amount' => 1000,
    ]);

    $rateioService = app(RateioService::class);
    $result = $rateioService->create([
        'category' => 'agua',
        'reference' => $reference,
        'total_amount' => 300,
        'split_mode' => RateioSplitMode::Residents,
        'property_ids' => [$propertyA->id, $propertyB->id],
    ]);

    expect($result['appliedCount'])->toBe(2);

    $chargeA->refresh();

    expect((float) $chargeA->rateio_amount)->toBeGreaterThan(0)
        ->and((float) $chargeA->original_amount)->toBeGreaterThan(1000)
        ->and($chargeA->status)->toBe(ChargeStatus::Open);

    expect(
        RateioAllocation::query()->where('charge_id', $chargeA->id)->whereNotNull('applied_at')->exists(),
    )->toBeTrue();
});
