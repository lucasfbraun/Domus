<?php

use App\Enums\ChargeStatus;
use App\Enums\DepositStatus;
use App\Jobs\SyncPendingPixPaymentsJob;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\Deposit;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Notifications\PaymentConfirmedNotification;
use App\Services\MercadoPagoService;
use App\Services\ReminderService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    config([
        'services.mercadopago.access_token' => 'APP_USR-test-access-token',
        'services.mercadopago.client_id' => '123456',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.sandbox_connect' => true,
    ]);
});

function processedOrderResponse(string $orderId, string $externalReference, float $amount): array
{
    return [
        'id' => $orderId,
        'status' => 'processed',
        'status_detail' => 'accredited',
        'external_reference' => $externalReference,
        'total_amount' => (string) $amount,
        'total_paid_amount' => (string) $amount,
        'updated_date' => now()->toIso8601String(),
        'transactions' => [
            'payments' => [
                [
                    'id' => 'PAY-'.$orderId,
                    'paid_amount' => (string) $amount,
                    'status' => 'processed',
                    'payment_method' => ['id' => 'pix', 'type' => 'bank_transfer'],
                ],
            ],
        ],
    ];
}

function pendingChargeWithOrder(string $orderId, ChargeStatus $status = ChargeStatus::WaitingPayment): Charge
{
    $tenant = Tenant::factory()->create();
    $receiver = Receiver::factory()->create();
    $contract = Contract::factory()->active()->for($tenant)->for($receiver)->create();

    return Charge::factory()->for($contract)->for($receiver)->create([
        'status' => $status,
        'original_amount' => 1500.50,
        'mercado_pago_order_id' => $orderId,
        'pix_expires_at' => now()->addMinutes(30),
    ]);
}

test('marks a waiting-payment charge as paid when its order is processed', function () {
    Notification::fake();
    $charge = pendingChargeWithOrder('ORD-WAITING');

    Http::fake([
        'https://api.mercadopago.com/v1/orders/ORD-WAITING' => Http::response(
            processedOrderResponse('ORD-WAITING', (string) $charge->id, 1500.50),
        ),
    ]);

    (new SyncPendingPixPaymentsJob)->handle(app(MercadoPagoService::class), app(ReminderService::class));

    expect($charge->fresh()->status)->toBe(ChargeStatus::Paid);
    Notification::assertSentTo($charge->fresh()->contract->tenant, PaymentConfirmedNotification::class);
});

test('also syncs an overdue charge whose pix has not expired yet', function () {
    Notification::fake();
    $charge = pendingChargeWithOrder('ORD-OVERDUE', ChargeStatus::Overdue);

    Http::fake([
        'https://api.mercadopago.com/v1/orders/ORD-OVERDUE' => Http::response(
            processedOrderResponse('ORD-OVERDUE', (string) $charge->id, 1500.50),
        ),
    ]);

    (new SyncPendingPixPaymentsJob)->handle(app(MercadoPagoService::class), app(ReminderService::class));

    expect($charge->fresh()->status)->toBe(ChargeStatus::Paid);
});

test('skips a charge whose pix already expired, without calling the api', function () {
    $charge = pendingChargeWithOrder('ORD-EXPIRED');
    $charge->update(['pix_expires_at' => now()->subDay()]);

    Http::fake();

    (new SyncPendingPixPaymentsJob)->handle(app(MercadoPagoService::class), app(ReminderService::class));

    Http::assertNothingSent();
    expect($charge->fresh()->status)->toBe(ChargeStatus::WaitingPayment);
});

test('skips a charge with no pix order at all', function () {
    $tenant = Tenant::factory()->create();
    $receiver = Receiver::factory()->create();
    $contract = Contract::factory()->active()->for($tenant)->for($receiver)->create();
    Charge::factory()->for($contract)->for($receiver)->create([
        'status' => ChargeStatus::WaitingPayment,
        'mercado_pago_order_id' => null,
    ]);

    Http::fake();

    (new SyncPendingPixPaymentsJob)->handle(app(MercadoPagoService::class), app(ReminderService::class));

    Http::assertNothingSent();
});

test('one charge failing to sync does not stop the rest of the batch', function () {
    Notification::fake();
    $failing = pendingChargeWithOrder('ORD-FAILS');
    $succeeding = pendingChargeWithOrder('ORD-SUCCEEDS');

    Http::fake([
        'https://api.mercadopago.com/v1/orders/ORD-FAILS' => Http::response(['message' => 'boom'], 500),
        'https://api.mercadopago.com/v1/orders/ORD-SUCCEEDS' => Http::response(
            processedOrderResponse('ORD-SUCCEEDS', (string) $succeeding->id, 1500.50),
        ),
    ]);

    (new SyncPendingPixPaymentsJob)->handle(app(MercadoPagoService::class), app(ReminderService::class));

    expect($failing->fresh()->status)->toBe(ChargeStatus::WaitingPayment)
        ->and($succeeding->fresh()->status)->toBe(ChargeStatus::Paid);
});

test('also marks a waiting-payment deposit as paid when its order is processed', function () {
    $tenant = Tenant::factory()->create();
    $receiver = Receiver::factory()->create();
    $contract = Contract::factory()->active()->for($tenant)->for($receiver)->create();
    $deposit = Deposit::factory()->for($contract)->for($receiver)->create([
        'status' => DepositStatus::WaitingPayment,
        'amount' => 1000,
        'mercado_pago_order_id' => 'ORD-DEPOSIT',
        'pix_expires_at' => now()->addMinutes(30),
    ]);

    Http::fake([
        'https://api.mercadopago.com/v1/orders/ORD-DEPOSIT' => Http::response(
            processedOrderResponse('ORD-DEPOSIT', (string) $deposit->id, 1000),
        ),
    ]);

    (new SyncPendingPixPaymentsJob)->handle(app(MercadoPagoService::class), app(ReminderService::class));

    expect($deposit->fresh()->status)->toBe(DepositStatus::Paid);
});
