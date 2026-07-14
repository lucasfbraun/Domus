<?php

use App\Enums\ChargeStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MercadoPagoService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    config([
        'services.mercadopago.access_token' => 'APP_USR-test-access-token',
        'services.mercadopago.public_key' => 'APP_USR-test-public-key',
        'services.mercadopago.webhook_secret' => 'test-webhook-secret',
        'services.mercadopago.client_id' => '123456',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.sandbox_connect' => true,
    ]);
});

function makeOpenCharge(): Charge
{
    $tenant = Tenant::factory()->create([
        'email' => 'tenant@example.com',
        'document' => '52998224725',
        'name' => 'Joao Silva',
    ]);

    $receiver = Receiver::factory()->create();

    $contract = Contract::factory()
        ->for($tenant)
        ->for($receiver)
        ->active()
        ->create([
            'grace_days' => 0,
            'fine_rate' => 0,
            'monthly_interest_rate' => 0,
        ]);

    return Charge::factory()
        ->open()
        ->for($contract)
        ->for($receiver)
        ->create([
            'original_amount' => 1500.50,
        ]);
}

test('createPixCharge reutiliza pix valido sem criar nova order', function () {
    $charge = makeOpenCharge();
    $charge->update([
        'status' => ChargeStatus::WaitingPayment,
        'mercado_pago_order_id' => 'ORD01EXISTING',
        'mercado_pago_transaction_id' => 'PAY01EXISTING',
        'pix_qr_code' => '00020126existing-pix',
        'pix_qr_code_base64' => 'base64existing',
        'pix_expires_at' => now()->addMinutes(30),
        'payment_url' => 'https://www.mercadopago.com.br/sandbox/payments/ticket',
    ]);

    Http::fake();

    $result = app(MercadoPagoService::class)->createPixCharge($charge->fresh());

    expect($result['orderId'])->toBe('ORD01EXISTING')
        ->and($result['qrCode'])->toBe('00020126existing-pix')
        ->and($result['qrCodeBase64'])->toBe('base64existing');

    Http::assertNothingSent();
});

test('createPixCharge cria order na Orders API e grava dados do Pix', function () {
    $charge = makeOpenCharge();

    Http::fake([
        'https://api.mercadopago.com/v1/orders' => Http::response([
            'id' => 'ORD01TESTORDER123',
            'status' => 'action_required',
            'status_detail' => 'waiting_transfer',
            'external_reference' => (string) $charge->id,
            'transactions' => [
                'payments' => [
                    [
                        'id' => 'PAY01TESTPAYMENT123',
                        'status' => 'action_required',
                        'payment_method' => [
                            'id' => 'pix',
                            'type' => 'bank_transfer',
                            'qr_code' => '00020126pix-copy-paste',
                            'qr_code_base64' => 'base64qr',
                            'ticket_url' => 'https://www.mercadopago.com.br/sandbox/payments/ticket',
                        ],
                    ],
                ],
            ],
        ], 201),
    ]);

    $result = app(MercadoPagoService::class)->createPixCharge($charge);

    expect($result['orderId'])->toBe('ORD01TESTORDER123')
        ->and($result['transactionId'])->toBe('PAY01TESTPAYMENT123')
        ->and($result['qrCode'])->toBe('00020126pix-copy-paste')
        ->and($charge->fresh()->status)->toBe(ChargeStatus::WaitingPayment)
        ->and($charge->fresh()->mercado_pago_order_id)->toBe('ORD01TESTORDER123')
        ->and($charge->fresh()->mercado_pago_transaction_id)->toBe('PAY01TESTPAYMENT123')
        ->and($charge->fresh()->payment_url)->toContain('ticket');

    Http::assertSent(function ($request) use ($charge) {
        return $request->url() === 'https://api.mercadopago.com/v1/orders'
            && $request['type'] === 'online'
            && $request['processing_mode'] === 'automatic'
            && $request['external_reference'] === (string) $charge->id
            && $request['transactions']['payments'][0]['payment_method']['id'] === 'pix'
            && $request['transactions']['payments'][0]['expiration_time'] === 'PT1H'
            && $request->hasHeader('X-Idempotency-Key');
    });
});

test('syncChargePayment marca cobranca como paga quando order esta processed', function () {
    $charge = makeOpenCharge();
    $charge->update([
        'status' => ChargeStatus::WaitingPayment,
        'mercado_pago_order_id' => 'ORD01TESTORDER123',
    ]);

    Http::fake([
        'https://api.mercadopago.com/v1/orders/ORD01TESTORDER123' => Http::response([
            'id' => 'ORD01TESTORDER123',
            'status' => 'processed',
            'status_detail' => 'accredited',
            'external_reference' => (string) $charge->id,
            'total_amount' => '1500.50',
            'total_paid_amount' => '1500.50',
            'updated_date' => '2026-07-13T20:00:00Z',
            'transactions' => [
                'payments' => [
                    [
                        'id' => 'PAY01TESTPAYMENT123',
                        'paid_amount' => '1500.50',
                        'status' => 'processed',
                        'payment_method' => [
                            'id' => 'pix',
                            'type' => 'bank_transfer',
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $result = app(MercadoPagoService::class)->syncChargePayment($charge);

    expect($result['updated'])->toBeTrue()
        ->and($result['status'])->toBe('processed')
        ->and($charge->fresh()->status)->toBe(ChargeStatus::Paid)
        ->and(Payment::query()->where('external_id', 'ORD01TESTORDER123')->exists())->toBeTrue()
        ->and(Payment::query()->first()->method)->toBe(PaymentMethod::Pix)
        ->and(Payment::query()->first()->status)->toBe(PaymentStatus::Approved);
});

test('webhook order.processed registra pagamento e responde ok', function () {
    $charge = makeOpenCharge();
    $charge->update([
        'status' => ChargeStatus::WaitingPayment,
        'mercado_pago_order_id' => 'ORD01TESTORDER123',
    ]);

    Http::fake([
        'https://api.mercadopago.com/v1/orders/ORD01TESTORDER123' => Http::response([
            'id' => 'ORD01TESTORDER123',
            'status' => 'processed',
            'status_detail' => 'accredited',
            'external_reference' => (string) $charge->id,
            'total_paid_amount' => '1500.50',
            'updated_date' => '2026-07-13T20:00:00Z',
            'transactions' => [
                'payments' => [
                    [
                        'id' => 'PAY01TESTPAYMENT123',
                        'paid_amount' => '1500.50',
                        'payment_method' => [
                            'id' => 'pix',
                            'type' => 'bank_transfer',
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $dataId = 'ORD01TESTORDER123';
    $requestId = 'req-123';
    $ts = (string) now()->timestamp;
    $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
    $signature = 'ts='.$ts.',v1='.hash_hmac('sha256', $manifest, 'test-webhook-secret');

    $response = $this->postJson('/webhooks/mercadopago?data.id='.$dataId.'&type=order', [
        'action' => 'order.processed',
        'api_version' => 'v1',
        'type' => 'order',
        'data' => ['id' => $dataId],
    ], [
        'x-signature' => $signature,
        'x-request-id' => $requestId,
    ]);

    $response->assertSuccessful()
        ->assertJson(['handled' => true, 'status' => 'processed']);

    expect($charge->fresh()->status)->toBe(ChargeStatus::Paid);
});

test('webhook rejeita assinatura invalida', function () {
    $response = $this->postJson('/webhooks/mercadopago', [
        'action' => 'order.processed',
        'type' => 'order',
        'data' => ['id' => 'ORD01TESTORDER123'],
    ], [
        'x-signature' => 'ts=1,v1=invalid',
        'x-request-id' => 'req-1',
    ]);

    $response->assertUnauthorized();
});

test('createPixCharge rejeita token TEST da plataforma', function () {
    $charge = makeOpenCharge();

    config([
        'app.env' => 'local',
        'services.mercadopago.access_token' => 'TEST-platform-token',
    ]);

    $charge->receiver->update([
        'mp_access_token' => null,
        'mp_refresh_token' => null,
        'mp_user_id' => null,
        'mp_connected_at' => null,
        'mp_live_mode' => null,
    ]);

    expect(fn () => app(MercadoPagoService::class)->createPixCharge($charge->fresh(['contract.tenant', 'receiver'])))
        ->toThrow(InvalidArgumentException::class, 'TEST-');
});

test('createPixCharge rejeita recebedor conectado em modo teste', function () {
    $charge = makeOpenCharge();

    $charge->receiver->update([
        'mp_user_id' => '218971996',
        'mp_access_token' => 'TEST-receiver-token',
        'mp_refresh_token' => 'TEST-refresh',
        'mp_token_expires_at' => now()->addHours(6),
        'mp_connected_at' => now(),
        'mp_live_mode' => false,
    ]);

    expect(fn () => app(MercadoPagoService::class)->createPixCharge($charge->fresh(['contract.tenant', 'receiver'])))
        ->toThrow(InvalidArgumentException::class, 'credenciais de teste');
});

test('createPix retorna erro amigavel quando Mercado Pago falha', function () {
    $charge = makeOpenCharge();

    Http::fake([
        'https://api.mercadopago.com/v1/orders' => Http::response([
            'errors' => [[
                'code' => 'failed',
                'message' => 'The following transactions failed',
                'details' => ['PAY01TEST: processing_error'],
            ]],
        ], 402),
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson(route('charges.pix', $charge))
        ->assertStatus(502)
        ->assertJsonFragment([
            'message' => 'O Mercado Pago recusou gerar este Pix (erro de processamento). No sandbox, valores acima de R$ 1.000 costumam falhar — em produção o valor com juros/multa deve funcionar normalmente.',
        ]);
});

test('createPix retorna 422 quando recebedor usa credenciais de teste', function () {
    $charge = makeOpenCharge();

    $charge->receiver->update([
        'mp_user_id' => '218971996',
        'mp_access_token' => 'TEST-receiver-token',
        'mp_refresh_token' => 'TEST-refresh',
        'mp_token_expires_at' => now()->addHours(6),
        'mp_connected_at' => now(),
        'mp_live_mode' => false,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->postJson(route('charges.pix', $charge))
        ->assertUnprocessable()
        ->assertJsonFragment([
            'message' => 'O Mercado Pago rejeitou as credenciais de teste. A Orders API exige token de produção (APP_USR-): defina MP_SANDBOX_CONNECT=false no servidor e reconecte o recebedor.',
        ]);
});
