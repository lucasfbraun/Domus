<?php

use App\Models\Contract;
use App\Models\ContractWitness;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Receiver;
use App\Services\ContractSignatureService;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('contract is ready when no owner and no witnesses', function () {
    $property = Property::factory()->create(['owner_id' => null]);
    $contract = Contract::factory()->active()->for($property)->create();

    $service = app(ContractSignatureService::class);

    expect($service->isContractReadyForTenantSignature($contract))->toBeTrue();
});

test('contract requires owner signature when property has owner', function () {
    $owner = Owner::factory()->create();
    $property = Property::factory()->for($owner)->create();
    $contract = Contract::factory()->active()->for($property)->create(['owner_signed_at' => null]);

    $service = app(ContractSignatureService::class);

    expect($service->isContractReadyForTenantSignature($contract))->toBeFalse();

    $contract->update(['owner_signed_at' => now()]);

    expect($service->isContractReadyForTenantSignature($contract->fresh()))->toBeTrue();
});

test('contract requires all witnesses to sign', function () {
    $property = Property::factory()->create(['owner_id' => null]);
    $contract = Contract::factory()->active()->for($property)->create();
    $receiver = Receiver::factory()->create();

    ContractWitness::factory()->for($contract)->for($receiver)->create(['signed_at' => null]);

    $service = app(ContractSignatureService::class);

    expect($service->isContractReadyForTenantSignature($contract))->toBeFalse();

    $contract->witnesses()->first()->update(['signed_at' => now()]);

    expect($service->isContractReadyForTenantSignature($contract->fresh()))->toBeTrue();
});
