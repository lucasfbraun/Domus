<?php

use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Contract;
use App\Models\Deposit;
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

function makeDeposit(): Deposit
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
        ->create();

    return Deposit::factory()
        ->for($contract)
        ->for($receiver)
        ->create([
            'amount' => 2000,
        ]);
}

test('admin pode cadastrar uma caução vinculada a um contrato', function () {
    $admin = User::factory()->admin()->create();
    $tenant = Tenant::factory()->create();
    $receiver = Receiver::factory()->create();
    $contract = Contract::factory()->for($tenant)->for($receiver)->active()->create();

    $this->actingAs($admin)
        ->post(route('admin.deposits.store'), [
            'contract_id' => $contract->id,
            'receiver_id' => $receiver->id,
            'description' => 'Caução do contrato',
            'amount' => 1500,
            'due_date' => now()->addDays(10)->toDateString(),
        ])
        ->assertRedirect();

    expect(Deposit::query()->count())->toBe(1)
        ->and(Deposit::query()->first()->status)->toBe(DepositStatus::Pending);
});

test('createPixForDeposit cria order na Orders API e grava dados do Pix', function () {
    $deposit = makeDeposit();

    Http::fake([
        'https://api.mercadopago.com/v1/orders' => Http::response([
            'id' => 'ORD01DEPOSIT123',
            'status' => 'action_required',
            'external_reference' => 'deposit:'.$deposit->id,
            'transactions' => [
                'payments' => [
                    [
                        'id' => 'PAY01DEPOSIT123',
                        'payment_method' => [
                            'id' => 'pix',
                            'type' => 'bank_transfer',
                            'qr_code' => '00020126deposit-pix',
                            'qr_code_base64' => 'base64qr',
                            'ticket_url' => 'https://www.mercadopago.com.br/sandbox/payments/ticket',
                        ],
                    ],
                ],
            ],
        ], 201),
    ]);

    $result = app(MercadoPagoService::class)->createPixForDeposit($deposit);

    expect($result['orderId'])->toBe('ORD01DEPOSIT123')
        ->and($result['qrCode'])->toBe('00020126deposit-pix')
        ->and($deposit->fresh()->status)->toBe(DepositStatus::WaitingPayment)
        ->and($deposit->fresh()->mercado_pago_order_id)->toBe('ORD01DEPOSIT123');

    Http::assertSent(function ($request) use ($deposit) {
        return $request->url() === 'https://api.mercadopago.com/v1/orders'
            && $request['external_reference'] === 'deposit:'.$deposit->id;
    });
});

test('syncDepositPayment marca caução como paga e cria payment vinculado ao deposit', function () {
    $deposit = makeDeposit();
    $deposit->update([
        'status' => DepositStatus::WaitingPayment,
        'mercado_pago_order_id' => 'ORD01DEPOSIT123',
    ]);

    Http::fake([
        'https://api.mercadopago.com/v1/orders/ORD01DEPOSIT123' => Http::response([
            'id' => 'ORD01DEPOSIT123',
            'status' => 'processed',
            'external_reference' => 'deposit:'.$deposit->id,
            'total_paid_amount' => '2000.00',
            'updated_date' => '2026-07-18T20:00:00Z',
            'transactions' => [
                'payments' => [
                    [
                        'id' => 'PAY01DEPOSIT123',
                        'paid_amount' => '2000.00',
                        'payment_method' => ['id' => 'pix', 'type' => 'bank_transfer'],
                    ],
                ],
            ],
        ]),
    ]);

    $result = app(MercadoPagoService::class)->syncDepositPayment($deposit);

    expect($result['updated'])->toBeTrue()
        ->and($deposit->fresh()->status)->toBe(DepositStatus::Paid)
        ->and(Payment::query()->where('external_id', 'ORD01DEPOSIT123')->exists())->toBeTrue()
        ->and(Payment::query()->first()->deposit_id)->toBe($deposit->id)
        ->and(Payment::query()->first()->charge_id)->toBeNull()
        ->and(Payment::query()->first()->status)->toBe(PaymentStatus::Approved)
        ->and(Payment::query()->first()->method)->toBe(PaymentMethod::Pix);
});

test('webhook order.processed com referencia de deposito registra pagamento', function () {
    $deposit = makeDeposit();
    $deposit->update([
        'status' => DepositStatus::WaitingPayment,
        'mercado_pago_order_id' => 'ORD01DEPOSIT123',
    ]);

    Http::fake([
        'https://api.mercadopago.com/v1/orders/ORD01DEPOSIT123' => Http::response([
            'id' => 'ORD01DEPOSIT123',
            'status' => 'processed',
            'external_reference' => 'deposit:'.$deposit->id,
            'total_paid_amount' => '2000.00',
            'updated_date' => '2026-07-18T20:00:00Z',
            'transactions' => [
                'payments' => [
                    [
                        'id' => 'PAY01DEPOSIT123',
                        'paid_amount' => '2000.00',
                        'payment_method' => ['id' => 'pix', 'type' => 'bank_transfer'],
                    ],
                ],
            ],
        ]),
    ]);

    $dataId = 'ORD01DEPOSIT123';
    $requestId = 'req-456';
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

    expect($deposit->fresh()->status)->toBe(DepositStatus::Paid);
});

test('admin so pode marcar caução como devolvida depois de paga', function () {
    $admin = User::factory()->admin()->create();
    $deposit = makeDeposit();

    $this->actingAs($admin)
        ->post(route('admin.deposits.refund', $deposit))
        ->assertSessionHasErrors('refund');

    expect($deposit->fresh()->status)->toBe(DepositStatus::Pending);

    $deposit->update(['status' => DepositStatus::Paid, 'paid_at' => now()]);

    $this->actingAs($admin)
        ->post(route('admin.deposits.refund', $deposit), [
            'refunded_amount' => 2000,
            'refund_note' => 'Devolvido na saída do inquilino',
        ])
        ->assertRedirect();

    expect($deposit->fresh()->status)->toBe(DepositStatus::Refunded)
        ->and($deposit->fresh()->refunded_amount)->toEqual('2000.00');
});

test('admin can update a deposit', function () {
    $admin = User::factory()->admin()->create();
    $deposit = makeDeposit();

    $this->actingAs($admin)
        ->put(route('admin.deposits.update', $deposit), [
            'contract_id' => $deposit->contract_id,
            'receiver_id' => $deposit->receiver_id,
            'description' => 'Caução atualizada',
            'amount' => 2500,
            'due_date' => now()->addDays(20)->toDateString(),
        ])
        ->assertRedirect();

    expect($deposit->fresh()->description)->toBe('Caução atualizada')
        ->and((float) $deposit->fresh()->amount)->toBe(2500.0);
});

test('non admin cannot update a deposit', function () {
    $tenantUser = User::factory()->tenant()->create();
    $deposit = makeDeposit();

    $this->actingAs($tenantUser)
        ->put(route('admin.deposits.update', $deposit), [
            'contract_id' => $deposit->contract_id,
            'receiver_id' => $deposit->receiver_id,
            'description' => 'x',
            'amount' => 100,
            'due_date' => now()->toDateString(),
        ])
        ->assertForbidden();
});

test('admin can delete a deposit', function () {
    $admin = User::factory()->admin()->create();
    $deposit = makeDeposit();

    $this->actingAs($admin)
        ->delete(route('admin.deposits.destroy', $deposit))
        ->assertRedirect();

    expect(Deposit::query()->find($deposit->id))->toBeNull();
});

test('non admin cannot delete a deposit', function () {
    $tenantUser = User::factory()->tenant()->create();
    $deposit = makeDeposit();

    $this->actingAs($tenantUser)
        ->delete(route('admin.deposits.destroy', $deposit))
        ->assertForbidden();

    expect(Deposit::query()->find($deposit->id))->not->toBeNull();
});

test('admin can create a pix charge for a deposit via http', function () {
    $admin = User::factory()->admin()->create();
    $deposit = makeDeposit();

    Http::fake([
        'https://api.mercadopago.com/v1/orders' => Http::response([
            'id' => 'ORD-HTTP-DEPOSIT',
            'status' => 'action_required',
            'external_reference' => 'deposit:'.$deposit->id,
            'transactions' => [
                'payments' => [[
                    'id' => 'PAY-HTTP-DEPOSIT',
                    'payment_method' => [
                        'id' => 'pix',
                        'type' => 'bank_transfer',
                        'qr_code' => '00020126http-deposit-pix',
                        'qr_code_base64' => 'base64qr',
                        'ticket_url' => 'https://www.mercadopago.com.br/sandbox/payments/ticket',
                    ],
                ]],
            ],
        ], 201),
    ]);

    $this->actingAs($admin)
        ->post(route('deposits.pix', $deposit))
        ->assertRedirect();

    expect($deposit->fresh()->status)->toBe(DepositStatus::WaitingPayment);
});

test('the owning tenant can also create a pix charge for their own deposit', function () {
    $deposit = makeDeposit();
    $tenantUser = User::factory()->tenant()->create();
    $deposit->contract->tenant->update(['user_id' => $tenantUser->id]);

    Http::fake([
        'https://api.mercadopago.com/v1/orders' => Http::response([
            'id' => 'ORD-TENANT-DEPOSIT',
            'status' => 'action_required',
            'external_reference' => 'deposit:'.$deposit->id,
            'transactions' => [
                'payments' => [[
                    'id' => 'PAY-TENANT-DEPOSIT',
                    'payment_method' => ['id' => 'pix', 'type' => 'bank_transfer', 'qr_code' => 'x', 'qr_code_base64' => 'y', 'ticket_url' => 'z'],
                ]],
            ],
        ], 201),
    ]);

    $this->actingAs($tenantUser)
        ->post(route('deposits.pix', $deposit))
        ->assertRedirect();
});

test('a receiver cannot create a pix charge for a deposit', function () {
    $deposit = makeDeposit();
    $receiverUser = User::factory()->receiver()->create();
    $deposit->receiver->update(['user_id' => $receiverUser->id]);

    $this->actingAs($receiverUser)
        ->post(route('deposits.pix', $deposit))
        ->assertForbidden();
});

test('admin can sync a deposit payment via http', function () {
    $admin = User::factory()->admin()->create();
    $deposit = makeDeposit();
    $deposit->update(['status' => DepositStatus::WaitingPayment, 'mercado_pago_order_id' => 'ORD-SYNC-DEPOSIT']);

    Http::fake([
        'https://api.mercadopago.com/v1/orders/ORD-SYNC-DEPOSIT' => Http::response([
            'id' => 'ORD-SYNC-DEPOSIT',
            'status' => 'processed',
            'external_reference' => 'deposit:'.$deposit->id,
            'total_paid_amount' => '2000.00',
            'updated_date' => now()->toIso8601String(),
            'transactions' => [
                'payments' => [[
                    'id' => 'PAY-SYNC-DEPOSIT',
                    'paid_amount' => '2000.00',
                    'payment_method' => ['id' => 'pix', 'type' => 'bank_transfer'],
                ]],
            ],
        ]),
    ]);

    $this->actingAs($admin)
        ->post(route('deposits.sync', $deposit))
        ->assertRedirect();

    expect($deposit->fresh()->status)->toBe(DepositStatus::Paid);
});

test('inquilino ve sua caução no portal', function () {
    $deposit = makeDeposit();
    $tenant = $deposit->contract->tenant;
    $user = User::factory()->tenant()->create();
    $tenant->update(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('tenant.portal'))
        ->assertOk();
});
