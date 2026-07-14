<?php

use App\Models\Charge;
use App\Models\Contract;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MercadoPagoService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    config([
        'services.mercadopago.access_token' => 'APP_USR-test-access-token',
        'services.mercadopago.client_id' => '123456',
        'services.mercadopago.client_secret' => 'client-secret',
        'services.mercadopago.sandbox_connect' => true,
    ]);
});

function makeChargeForOAuthTests(): Charge
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
            'original_amount' => 900,
        ]);
}

function oauthStateForReceiver(Receiver $receiver): string
{
    $url = app(MercadoPagoService::class)->getAuthorizationUrl(
        $receiver,
        route('admin.receivers.mercadopago.callback'),
    );

    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    return (string) $query['state'];
}

test('admin connect mercado pago redirects to oauth authorization url', function () {
    $receiver = Receiver::factory()->create();
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.receivers.mercadopago.connect', $receiver));

    $response->assertRedirect();

    expect($response->headers->get('Location'))
        ->toStartWith('https://auth.mercadopago.com/authorization')
        ->toContain('client_id=123456')
        ->toContain('redirect_uri=');
});

test('admin connect mercado pago uses inertia location for xhr visits', function () {
    $receiver = Receiver::factory()->create();
    $admin = User::factory()->admin()->create();

    // Warm the Inertia version callback via a normal page visit.
    $this->actingAs($admin)->get(route('admin.receivers.edit', $receiver));

    $response = $this->actingAs($admin)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->get(route('admin.receivers.mercadopago.connect', $receiver));

    $response->assertStatus(409);

    expect($response->headers->get('X-Inertia-Location'))
        ->toStartWith('https://auth.mercadopago.com/authorization');
});

test('oauth callback saves connection and redirects to receiver edit', function () {
    $receiver = Receiver::factory()->create();
    $admin = User::factory()->admin()->create();
    $state = oauthStateForReceiver($receiver);

    Http::fake([
        'https://api.mercadopago.com/oauth/token' => Http::response([
            'access_token' => 'APP_USR-test-oauth-token',
            'refresh_token' => 'refresh-token',
            'expires_in' => 21_600,
            'user_id' => 12_345_678,
            'live_mode' => false,
        ]),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.receivers.mercadopago.callback', [
            'code' => 'auth-code',
            'state' => $state,
        ]))
        ->assertRedirect(route('admin.receivers.edit', $receiver));

    $receiver->refresh();

    expect($receiver->mp_user_id)->toBe('12345678')
        ->and($receiver->mp_connected_at)->not->toBeNull()
        ->and($receiver->mp_live_mode)->toBeFalse()
        ->and($receiver->mp_access_token)->toBe('APP_USR-test-oauth-token');
});

test('admin can disconnect mercado pago from receiver', function () {
    $receiver = Receiver::factory()->connected()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.receivers.mercadopago.disconnect', $receiver))
        ->assertRedirect(route('admin.receivers.edit', $receiver));

    $receiver->refresh();

    expect($receiver->mp_user_id)->toBeNull()
        ->and($receiver->mp_access_token)->toBeNull()
        ->and($receiver->mp_refresh_token)->toBeNull()
        ->and($receiver->mp_connected_at)->toBeNull()
        ->and($receiver->mp_live_mode)->toBeNull()
        ->and($receiver->mp_connected)->toBeFalse();
});

test('createPixCharge exige oauth do recebedor em production', function () {
    $charge = makeChargeForOAuthTests();

    config([
        'app.env' => 'production',
        'services.mercadopago.access_token' => 'APP_USR-platform-token',
    ]);

    expect(fn () => app(MercadoPagoService::class)->createPixCharge($charge))
        ->toThrow(
            InvalidArgumentException::class,
            'Este recebedor ainda nao conectou a conta Mercado Pago.',
        );
});

test('createPixCharge usa MP_ACCESS_TOKEN em testing sem oauth do recebedor', function () {
    $charge = makeChargeForOAuthTests();

    config([
        'app.env' => 'testing',
        'services.mercadopago.access_token' => 'APP_USR-test-access-token',
    ]);

    Http::fake([
        'https://api.mercadopago.com/v1/orders' => Http::response([
            'id' => 'ORD01TESTORDER123',
            'status' => 'action_required',
            'external_reference' => (string) $charge->id,
            'transactions' => [
                'payments' => [
                    [
                        'id' => 'PAY01TESTPAYMENT123',
                        'payment_method' => [
                            'id' => 'pix',
                            'type' => 'bank_transfer',
                            'qr_code' => '00020126pix-copy-paste',
                            'qr_code_base64' => 'base64qr',
                        ],
                    ],
                ],
            ],
        ], 201),
    ]);

    $result = app(MercadoPagoService::class)->createPixCharge($charge);

    expect($result['orderId'])->toBe('ORD01TESTORDER123');
});

test('integrations page shows mercado pago app status and connected receivers count', function () {
    Receiver::factory()->connected()->count(2)->create();
    Receiver::factory()->create();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.integrations.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Integrations')
            ->where('mercadoPago.appConfigured', true)
            ->where('mercadoPago.connectedReceiversCount', 2)
            ->where('mercadoPago.sandbox', true)
            ->where('mercadoPago.platformTokenConfigured', true));
});
