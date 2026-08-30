<?php

use App\Enums\ChargeStatus;
use App\Enums\RateioSplitMode;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\Property;
use App\Models\Rateio;
use App\Models\RateioAllocation;
use App\Models\Tenant;
use App\Services\BillingCycle;
use App\Services\RateioService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

/**
 * @return array{propertyA: Property, propertyB: Property, chargeA: Charge, chargeB: Charge, reference: string}
 */
function twoPropertiesWithOpenCharges(): array
{
    $propertyA = Property::factory()->create();
    $propertyB = Property::factory()->create();

    $contractA = Contract::factory()->active()->for($propertyA)->for(Tenant::factory())->create(['monthly_rent' => 1000]);
    $contractB = Contract::factory()->active()->for($propertyB)->for(Tenant::factory())->create(['monthly_rent' => 1000]);

    $today = BillingCycle::todayInSaoPaulo();
    $reference = BillingCycle::formatReference(
        BillingCycle::resolveBillingCycleDueDate($contractA->due_day, $today)['dueDateIso'],
    );

    $chargeA = Charge::factory()->open()->for($contractA)->for($contractA->receiver)->create([
        'reference' => $reference,
        'original_amount' => 1000,
    ]);
    $chargeB = Charge::factory()->open()->for($contractB)->for($contractB->receiver)->create([
        'reference' => $reference,
        'original_amount' => 1000,
    ]);

    return compact('propertyA', 'propertyB', 'chargeA', 'chargeB', 'reference');
}

test('equal split divides the total evenly between properties', function () {
    ['propertyA' => $propertyA, 'propertyB' => $propertyB, 'chargeA' => $chargeA, 'chargeB' => $chargeB, 'reference' => $reference] = twoPropertiesWithOpenCharges();

    $rateioService = app(RateioService::class);
    $result = $rateioService->create([
        'category' => 'condominio',
        'reference' => $reference,
        'total_amount' => 300,
        'split_mode' => RateioSplitMode::Equal,
        'property_ids' => [$propertyA->id, $propertyB->id],
    ]);

    expect($result['appliedCount'])->toBe(2)
        ->and((float) $chargeA->fresh()->rateio_amount)->toBe(150.0)
        ->and((float) $chargeB->fresh()->rateio_amount)->toBe(150.0);
});

test('updating a rateio reverses the old allocation before applying the new one', function () {
    ['propertyA' => $propertyA, 'propertyB' => $propertyB, 'chargeA' => $chargeA, 'reference' => $reference] = twoPropertiesWithOpenCharges();
    $rateioService = app(RateioService::class);

    $created = $rateioService->create([
        'category' => 'agua',
        'reference' => $reference,
        'total_amount' => 200,
        'split_mode' => RateioSplitMode::Equal,
        'property_ids' => [$propertyA->id, $propertyB->id],
    ]);

    expect((float) $chargeA->fresh()->rateio_amount)->toBe(100.0);

    $rateioService->update($created['rateio'], [
        'category' => 'agua',
        'reference' => $reference,
        'total_amount' => 400,
        'split_mode' => RateioSplitMode::Equal,
        'property_ids' => [$propertyA->id],
    ]);

    // Only propertyA is in the updated split, and it absorbs the whole
    // amount now — its previous 100 share must be gone, not stacked.
    expect((float) $chargeA->fresh()->rateio_amount)->toBe(400.0)
        ->and((float) $chargeA->fresh()->original_amount)->toBe(1400.0);
});

test('deleting a rateio reverses its allocations off the charge', function () {
    ['propertyA' => $propertyA, 'propertyB' => $propertyB, 'chargeA' => $chargeA, 'reference' => $reference] = twoPropertiesWithOpenCharges();
    $rateioService = app(RateioService::class);

    $created = $rateioService->create([
        'category' => 'gas',
        'reference' => $reference,
        'total_amount' => 200,
        'split_mode' => RateioSplitMode::Equal,
        'property_ids' => [$propertyA->id, $propertyB->id],
    ]);

    expect((float) $chargeA->fresh()->rateio_amount)->toBe(100.0);

    $rateioService->delete($created['rateio']);

    expect((float) $chargeA->fresh()->rateio_amount)->toBe(0.0)
        ->and((float) $chargeA->fresh()->original_amount)->toBe(1000.0)
        ->and(Rateio::query()->find($created['rateio']->id))->toBeNull();
});

test('a rateio already applied to a paid charge cannot be updated or deleted', function () {
    ['propertyA' => $propertyA, 'propertyB' => $propertyB, 'chargeA' => $chargeA, 'reference' => $reference] = twoPropertiesWithOpenCharges();
    $rateioService = app(RateioService::class);

    $created = $rateioService->create([
        'category' => 'iptu',
        'reference' => $reference,
        'total_amount' => 200,
        'split_mode' => RateioSplitMode::Equal,
        'property_ids' => [$propertyA->id, $propertyB->id],
    ]);

    $chargeA->update(['status' => ChargeStatus::Paid]);

    expect(fn () => $rateioService->update($created['rateio'], [
        'category' => 'iptu',
        'reference' => $reference,
        'total_amount' => 300,
        'split_mode' => RateioSplitMode::Equal,
        'property_ids' => [$propertyA->id, $propertyB->id],
    ]))->toThrow(InvalidArgumentException::class);

    expect(fn () => $rateioService->delete($created['rateio']))
        ->toThrow(InvalidArgumentException::class);

    expect(Rateio::query()->find($created['rateio']->id))->not->toBeNull();
});

test('create stores an uploaded invoice file', function () {
    Storage::fake('local');
    ['propertyA' => $propertyA, 'reference' => $reference] = twoPropertiesWithOpenCharges();

    $rateioService = app(RateioService::class);
    $result = $rateioService->create([
        'category' => 'agua',
        'reference' => $reference,
        'total_amount' => 100,
        'split_mode' => RateioSplitMode::Equal,
        'property_ids' => [$propertyA->id],
    ], UploadedFile::fake()->create('comprovante.pdf', 100, 'application/pdf'));

    $rateio = $result['rateio'];

    expect($rateio->invoice_path)->not->toBeNull()
        ->and($rateio->invoice_content_type)->toBe('application/pdf')
        ->and($rateio->invoice_file_name)->toBe('comprovante.pdf');
    Storage::disk('local')->assertExists($rateio->invoice_path);
});

test('create rejects an invoice file with an unsupported content type', function () {
    ['propertyA' => $propertyA, 'reference' => $reference] = twoPropertiesWithOpenCharges();
    $rateioService = app(RateioService::class);

    expect(fn () => $rateioService->create([
        'category' => 'agua',
        'reference' => $reference,
        'total_amount' => 100,
        'split_mode' => RateioSplitMode::Equal,
        'property_ids' => [$propertyA->id],
    ], UploadedFile::fake()->create('planilha.xlsx', 10, 'application/vnd.ms-excel')))
        ->toThrow(InvalidArgumentException::class);
});

test('create rejects an invoice file larger than the size limit', function () {
    ['propertyA' => $propertyA, 'reference' => $reference] = twoPropertiesWithOpenCharges();
    $rateioService = app(RateioService::class);

    expect(fn () => $rateioService->create([
        'category' => 'agua',
        'reference' => $reference,
        'total_amount' => 100,
        'split_mode' => RateioSplitMode::Equal,
        'property_ids' => [$propertyA->id],
    ], UploadedFile::fake()->create('grande.pdf', 8_193, 'application/pdf')))
        ->toThrow(InvalidArgumentException::class);
});
