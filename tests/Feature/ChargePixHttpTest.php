<?php

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    config([
        'services.mercadopago.access_token' => 'APP_USR-test-access-token',
        'services.mercadopago.client_id' => '123456',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.sandbox_connect' => true,
    ]);
});

function chargeForPixHttp(): Charge
{
    $contract = Contract::factory()->active()->create();

    return Charge::factory()->for($contract)->for($contract->receiver)->create([
        'status' => ChargeStatus::Open,
        'original_amount' => 1500,
        'due_date' => now()->addDays(10)->toDateString(),
    ]);
}

test('admin can create a pix charge for a charge via http', function () {
    $admin = User::factory()->admin()->create();
    $charge = chargeForPixHttp();

    Http::fake([
        'https://api.mercadopago.com/v1/orders' => Http::response([
            'id' => 'ORD-HTTP-CHARGE',
            'status' => 'action_required',
            'external_reference' => (string) $charge->id,
            'transactions' => [
                'payments' => [[
                    'id' => 'PAY-HTTP-CHARGE',
                    'payment_method' => [
                        'id' => 'pix',
                        'type' => 'bank_transfer',
                        'qr_code' => '00020126http-charge-pix',
                        'qr_code_base64' => 'base64qr',
                        'ticket_url' => 'https://www.mercadopago.com.br/sandbox/payments/ticket',
                    ],
                ]],
            ],
        ], 201),
    ]);

    $this->actingAs($admin)
        ->post(route('charges.pix', $charge))
        ->assertRedirect();

    expect($charge->fresh()->mercado_pago_order_id)->toBe('ORD-HTTP-CHARGE');
});

test('the owning tenant can also create a pix charge for their own charge', function () {
    $charge = chargeForPixHttp();
    $tenantUser = User::factory()->tenant()->create();
    $charge->contract->tenant->update(['user_id' => $tenantUser->id]);

    Http::fake([
        'https://api.mercadopago.com/v1/orders' => Http::response([
            'id' => 'ORD-TENANT-CHARGE',
            'status' => 'action_required',
            'external_reference' => (string) $charge->id,
            'transactions' => [
                'payments' => [[
                    'id' => 'PAY-TENANT-CHARGE',
                    'payment_method' => ['id' => 'pix', 'type' => 'bank_transfer', 'qr_code' => 'x', 'qr_code_base64' => 'y', 'ticket_url' => 'z'],
                ]],
            ],
        ], 201),
    ]);

    $this->actingAs($tenantUser)
        ->post(route('charges.pix', $charge))
        ->assertRedirect();
});

test('a tenant on a different contract cannot create a pix charge for someone elses charge', function () {
    $charge = chargeForPixHttp();
    $tenantUser = User::factory()->tenant()->create();
    Tenant::factory()->create(['user_id' => $tenantUser->id]);

    $this->actingAs($tenantUser)
        ->post(route('charges.pix', $charge))
        ->assertForbidden();
});

test('a receiver cannot create a pix charge for a charge', function () {
    $charge = chargeForPixHttp();
    $receiverUser = User::factory()->receiver()->create();
    Receiver::factory()->create(['user_id' => $receiverUser->id]);

    $this->actingAs($receiverUser)
        ->post(route('charges.pix', $charge))
        ->assertForbidden();
});

test('admin can sync a charge payment via http', function () {
    $admin = User::factory()->admin()->create();
    $charge = chargeForPixHttp();
    $charge->update(['status' => ChargeStatus::WaitingPayment, 'mercado_pago_order_id' => 'ORD-SYNC-CHARGE']);

    Http::fake([
        'https://api.mercadopago.com/v1/orders/ORD-SYNC-CHARGE' => Http::response([
            'id' => 'ORD-SYNC-CHARGE',
            'status' => 'processed',
            'status_detail' => 'accredited',
            'external_reference' => (string) $charge->id,
            'total_amount' => '1500.00',
            'total_paid_amount' => '1500.00',
            'updated_date' => now()->toIso8601String(),
            'transactions' => [
                'payments' => [[
                    'id' => 'PAY-SYNC-CHARGE',
                    'paid_amount' => '1500.00',
                    'status' => 'processed',
                    'payment_method' => ['id' => 'pix', 'type' => 'bank_transfer'],
                ]],
            ],
        ]),
    ]);

    $this->actingAs($admin)
        ->post(route('charges.sync', $charge))
        ->assertRedirect();

    expect($charge->fresh()->status)->toBe(ChargeStatus::Paid);
});

test('non admin, non owning users cannot sync a charge payment', function () {
    $charge = chargeForPixHttp();
    $receiverUser = User::factory()->receiver()->create();
    Receiver::factory()->create(['user_id' => $receiverUser->id]);

    $this->actingAs($receiverUser)
        ->post(route('charges.sync', $charge))
        ->assertForbidden();
});
