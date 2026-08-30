<?php

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractWitness;
use App\Models\Property;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ContractExpiringNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function validContractPayload(array $overrides = []): array
{
    return array_merge([
        'property_id' => Property::factory()->create()->id,
        'tenant_id' => Tenant::factory()->create()->id,
        'receiver_id' => Receiver::factory()->create()->id,
        'monthly_rent' => 1500,
        'due_day' => 10,
        'starts_at' => '2026-01-01',
        'ends_at' => '2026-12-31',
        'fine_percent' => 2,
        'interest_percent' => 1,
        'grace_days' => 3,
        'status' => ContractStatus::Active->value,
    ], $overrides);
}

test('non admin cannot access the contracts list', function () {
    $tenantUser = User::factory()->tenant()->create();

    $this->actingAs($tenantUser)
        ->get(route('admin.contracts.index'))
        ->assertForbidden();
});

test('admin sees the contracts list', function () {
    $admin = User::factory()->admin()->create();
    Contract::factory()->active()->create();

    $this->actingAs($admin)
        ->get(route('admin.contracts.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/contracts/Index')
            ->has('contracts.data', 1));
});

test('admin sees the create contract form with the reference data it needs', function () {
    $admin = User::factory()->admin()->create();
    Property::factory()->create();
    Tenant::factory()->create();
    Receiver::factory()->create();
    ContractTemplate::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.contracts.create'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/contracts/Form')
            ->where('contract', null)
            ->has('properties', 1)
            ->has('tenants', 1)
            ->has('receivers', 1)
            ->has('templates', 1));
});

test('admin sees a contract detail page', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create();

    $this->actingAs($admin)
        ->get(route('admin.contracts.show', $contract))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/contracts/Show')
            ->where('contract.id', $contract->id));
});

test('a tenant on a different contract cannot view someone elses contract detail', function () {
    $tenantUser = User::factory()->tenant()->create();
    Tenant::factory()->create(['user_id' => $tenantUser->id]);
    $otherContract = Contract::factory()->active()->create();

    $this->actingAs($tenantUser)
        ->get(route('admin.contracts.show', $otherContract))
        ->assertForbidden();
});

test('admin sees the edit contract form pre-filled with the contract', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create();

    $this->actingAs($admin)
        ->get(route('admin.contracts.edit', $contract))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/contracts/Form')
            ->where('contract.id', $contract->id));
});

test('admin can update a contract', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create(['monthly_rent' => 1000]);

    $this->actingAs($admin)
        ->put(route('admin.contracts.update', $contract), validContractPayload([
            'property_id' => $contract->property_id,
            'tenant_id' => $contract->tenant_id,
            'receiver_id' => $contract->receiver_id,
            'monthly_rent' => 1800,
        ]))
        ->assertRedirect(route('admin.contracts.show', $contract));

    expect((float) $contract->fresh()->monthly_rent)->toBe(1800.0);
});

test('updating a contract into expiring status sends the tenant an expiring reminder', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create(['status' => ContractStatus::Active]);

    $this->actingAs($admin)
        ->put(route('admin.contracts.update', $contract), validContractPayload([
            'property_id' => $contract->property_id,
            'tenant_id' => $contract->tenant_id,
            'receiver_id' => $contract->receiver_id,
            'status' => ContractStatus::Expiring->value,
        ]))
        ->assertRedirect();

    Notification::assertSentTo(
        $contract->tenant,
        ContractExpiringNotification::class,
    );
});

test('non admin cannot update a contract', function () {
    $tenantUser = User::factory()->tenant()->create();
    $contract = Contract::factory()->active()->create();

    $this->actingAs($tenantUser)
        ->put(route('admin.contracts.update', $contract), validContractPayload())
        ->assertForbidden();
});

test('admin can attach a witness to a contract', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create();
    $witness = Receiver::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.contracts.witnesses.attach', $contract), ['receiver_id' => $witness->id])
        ->assertRedirect();

    expect(ContractWitness::query()->where('contract_id', $contract->id)->where('receiver_id', $witness->id)->exists())->toBeTrue();
});

test('attaching the same witness twice does not duplicate the record', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create();
    $witness = Receiver::factory()->create();

    $this->actingAs($admin)->post(route('admin.contracts.witnesses.attach', $contract), ['receiver_id' => $witness->id]);
    $this->actingAs($admin)->post(route('admin.contracts.witnesses.attach', $contract), ['receiver_id' => $witness->id]);

    expect(ContractWitness::query()->where('contract_id', $contract->id)->count())->toBe(1);
});

test('admin can mark a witness as signed', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create();
    $witness = ContractWitness::factory()->for($contract)->create(['signed_at' => null]);

    $this->actingAs($admin)
        ->post(route('admin.contracts.witnesses.sign', [$contract, $witness]))
        ->assertRedirect();

    expect($witness->fresh()->signed_at)->not->toBeNull();
});

test('marking a witness from a different contract as signed 404s', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create();
    $otherContract = Contract::factory()->active()->create();
    $witness = ContractWitness::factory()->for($otherContract)->create();

    $this->actingAs($admin)
        ->post(route('admin.contracts.witnesses.sign', [$contract, $witness]))
        ->assertNotFound();
});
