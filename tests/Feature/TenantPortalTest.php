<?php

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MercadoPagoService;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('tenant portal only sees own contracts and charges', function () {
    $tenantUser = User::factory()->tenant()->create();
    $tenant = Tenant::factory()->create(['user_id' => $tenantUser->id]);

    $otherTenant = Tenant::factory()->create();

    $ownContract = Contract::factory()->active()->for($tenant)->create();
    $otherContract = Contract::factory()->active()->for($otherTenant)->create();

    $ownCharge = Charge::factory()->open()->for($ownContract)->for($ownContract->receiver)->create();
    Charge::factory()->open()->for($otherContract)->for($otherContract->receiver)->create();

    $response = $this->actingAs($tenantUser)
        ->get(route('tenant.portal'))
        ->assertSuccessful();

    $contracts = collect($response->viewData('page')['props']['contracts']['data']);
    $charges = collect($response->viewData('page')['props']['charges']['data']);

    expect($contracts)->toHaveCount(1)
        ->and($contracts->first()['id'])->toBe($ownContract->id)
        ->and($charges)->toHaveCount(1)
        ->and($charges->first()['id'])->toBe($ownCharge->id)
        ->and($charges->first()['has_pix'])->toBeFalse()
        ->and($charges->first()['pix_qr_code'])->toBeNull();
});

test('tenant portal inclui dados do pix quando a cobranca ja tem qr code', function () {
    $tenantUser = User::factory()->tenant()->create();
    $tenant = Tenant::factory()->create(['user_id' => $tenantUser->id]);
    $contract = Contract::factory()->active()->for($tenant)->create();

    $charge = Charge::factory()->open()->for($contract)->for($contract->receiver)->create([
        'status' => ChargeStatus::WaitingPayment,
        'mercado_pago_order_id' => 'ORDTST01DEMO',
        'pix_qr_code' => '00020126pix-copy-paste',
        'pix_qr_code_base64' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB',
        'pix_expires_at' => now()->addHour(),
    ]);

    $response = $this->actingAs($tenantUser)
        ->get(route('tenant.portal'))
        ->assertSuccessful();

    $charges = collect($response->viewData('page')['props']['charges']['data']);

    expect($charges->first()['id'])->toBe($charge->id)
        ->and($charges->first()['has_pix'])->toBeTrue()
        ->and($charges->first()['pix_qr_code'])->toBe('00020126pix-copy-paste')
        ->and($charges->first()['pix_qr_code_base64'])->toStartWith('iVBORw0KGgo');
});

test('tenant portal mostra valor com juros e multa em cobranca vencida', function () {
    $tenantUser = User::factory()->tenant()->create();
    $tenant = Tenant::factory()->create(['user_id' => $tenantUser->id]);

    $contract = Contract::factory()->active()->for($tenant)->create([
        'grace_days' => 0,
        'fine_rate' => 0.02,
        'monthly_interest_rate' => 0.01,
    ]);

    $charge = Charge::factory()->for($contract)->for($contract->receiver)->create([
        'original_amount' => 1000,
        'due_date' => now()->subDays(30)->toDateString(),
        'status' => ChargeStatus::Overdue,
    ]);

    $expectedDue = app(MercadoPagoService::class)
        ->computeCurrentAmountDue($charge->fresh(['contract']));

    $response = $this->actingAs($tenantUser)
        ->get(route('tenant.portal'))
        ->assertSuccessful();

    $charges = collect($response->viewData('page')['props']['charges']['data']);
    $payload = $charges->firstWhere('id', $charge->id);

    expect($payload['amount'])->toBe(1000.0)
        ->and($payload['amount_due'])->toBe($expectedDue)
        ->and($payload['has_penalties'])->toBeTrue()
        ->and($expectedDue)->toBeGreaterThan(1000.0);
});
