<?php

use App\Enums\ContractStatus;
use App\Enums\SignatureStatus;
use App\Models\Contract;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin can delete a draft contract with no document generated', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->create([
        'status' => ContractStatus::Draft,
        'signature_status' => SignatureStatus::NotGenerated,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.contracts.destroy', $contract))
        ->assertRedirect(route('admin.contracts.index'));

    expect(Contract::query()->find($contract->id))->toBeNull();
});

test('admin cannot delete an active contract', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create([
        'signature_status' => SignatureStatus::NotGenerated,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.contracts.destroy', $contract))
        ->assertRedirect();

    expect(Contract::query()->find($contract->id))->not->toBeNull();
});

test('admin cannot delete a draft contract that already has a signed document', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->create([
        'status' => ContractStatus::Draft,
        'signature_status' => SignatureStatus::Approved,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.contracts.destroy', $contract))
        ->assertRedirect();

    expect(Contract::query()->find($contract->id))->not->toBeNull();
});

test('admin cannot delete a draft contract that is merely awaiting signature', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->create([
        'status' => ContractStatus::Draft,
        'signature_status' => SignatureStatus::AwaitingSignature,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.contracts.destroy', $contract))
        ->assertRedirect();

    expect(Contract::query()->find($contract->id))->not->toBeNull();
});

test('admin cannot delete a cancelled contract', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->create([
        'status' => ContractStatus::Cancelled,
        'signature_status' => SignatureStatus::NotGenerated,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.contracts.destroy', $contract))
        ->assertRedirect();

    expect(Contract::query()->find($contract->id))->not->toBeNull();
});
