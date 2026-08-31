<?php

namespace App\Services;

use App\Enums\ChargeStatus;
use App\Enums\DepositStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Charge;
use App\Models\Deposit;
use App\Models\Payment;
use App\Models\Receiver;
use App\Support\Money;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Orders API (Pix) + OAuth Connect integration. Two separate credential
 * paths feed the same Orders API calls: a receiver's own OAuth-connected
 * `mp_access_token` (production), or the shared platform token in
 * `MP_ACCESS_TOKEN` as a local/CI shortcut for receivers who haven't
 * connected — see {@see allowsPlatformTokenFallback()} and
 * docs/adr/0010-mercadopago-platform-token-restricted-to-local.md for why
 * that fallback is hard-restricted to non-production environments.
 */
class MercadoPagoService
{
    private const OAUTH_AUTHORIZE_URL = 'https://auth.mercadopago.com/authorization';

    private const OAUTH_TOKEN_URL = 'https://api.mercadopago.com/oauth/token';

    private const ORDERS_URL = 'https://api.mercadopago.com/v1/orders';

    private const STATE_TTL_SECONDS = 20 * 60;

    private const TOKEN_REFRESH_MARGIN_SECONDS = 5 * 60;

    private const PIX_EXPIRATION = 'PT1H';

    public function getAuthorizationUrl(Receiver $receiver, string $redirectUri): string
    {
        $state = $this->signConnectState((string) $receiver->id);

        return self::OAUTH_AUTHORIZE_URL.'?'.http_build_query([
            'client_id' => config('services.mercadopago.client_id'),
            'response_type' => 'code',
            'platform_id' => 'mp',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);
    }

    /**
     * @return array{access_token: string, refresh_token?: string, expires_in: int, user_id: int, live_mode?: bool}
     */
    public function exchangeCodeForTokens(string $code, string $redirectUri): array
    {
        $this->assertOAuthCredentialsConfigured();

        $body = [
            'client_id' => config('services.mercadopago.client_id'),
            'client_secret' => config('services.mercadopago.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ];

        if (config('services.mercadopago.sandbox_connect')) {
            $body['test_token'] = true;
        }

        return $this->requestToken($body);
    }

    /**
     * @param  array{access_token: string, refresh_token?: string, expires_in: int, user_id: int, live_mode?: bool}  $token
     */
    public function saveReceiverConnection(Receiver $receiver, array $token): void
    {
        $receiver->update([
            'mp_user_id' => (string) $token['user_id'],
            'mp_access_token' => $token['access_token'],
            'mp_refresh_token' => $token['refresh_token'] ?? null,
            'mp_token_expires_at' => now()->addSeconds($token['expires_in']),
            'mp_connected_at' => now(),
            'mp_live_mode' => $this->isLiveToken($token),
        ]);
    }

    public function clearReceiverConnection(Receiver $receiver): void
    {
        $receiver->update([
            'mp_user_id' => null,
            'mp_access_token' => null,
            'mp_refresh_token' => null,
            'mp_token_expires_at' => null,
            'mp_connected_at' => null,
            'mp_live_mode' => null,
        ]);
    }

    /**
     * @return array{qrCode: string, qrCodeBase64: string, expiresAt: string, orderId: string, transactionId: string|null, ticketUrl: string|null}
     */
    public function createPixCharge(Charge $charge): array
    {
        $charge->loadMissing(['contract.tenant', 'receiver']);

        if (in_array($charge->status, [ChargeStatus::Paid, ChargeStatus::Cancelled], true)) {
            throw new \InvalidArgumentException('Esta cobranca nao esta em aberto.');
        }

        if ($this->hasReusablePix($charge)) {
            return [
                'qrCode' => (string) $charge->pix_qr_code,
                'qrCodeBase64' => (string) ($charge->pix_qr_code_base64 ?? ''),
                'expiresAt' => $charge->pix_expires_at?->toIso8601String() ?? now()->addHour()->toIso8601String(),
                'orderId' => (string) $charge->mercado_pago_order_id,
                'transactionId' => $charge->mercado_pago_transaction_id,
                'ticketUrl' => $charge->payment_url,
            ];
        }

        $accessToken = $this->ensureFreshAccessToken($charge->receiver);
        $amount = $this->formatAmount($this->computeCurrentAmountDue($charge));
        $expirationDate = now()->addHour();

        $tenant = $charge->contract->tenant;
        $nameParts = explode(' ', $tenant->name, 2);

        $response = $this->http($accessToken)
            ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
            ->post(self::ORDERS_URL, [
                'type' => 'online',
                'processing_mode' => 'automatic',
                'total_amount' => $amount,
                'external_reference' => (string) $charge->id,
                'description' => "Aluguel - cobranca {$charge->id}",
                'payer' => [
                    'email' => $tenant->email,
                    'first_name' => $nameParts[0] ?: $tenant->name,
                    'last_name' => $nameParts[1] ?? '-',
                    'identification' => $this->buildPayerIdentification($tenant->document),
                ],
                'transactions' => [
                    'payments' => [
                        [
                            'amount' => $amount,
                            'payment_method' => [
                                'id' => 'pix',
                                'type' => 'bank_transfer',
                            ],
                            'expiration_time' => self::PIX_EXPIRATION,
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Falha ao criar order Pix no Mercado Pago ('.$response->status().'): '.$response->body(),
            );
        }

        $order = $response->json();
        $payment = data_get($order, 'transactions.payments.0', []);
        $qrCode = (string) data_get($payment, 'payment_method.qr_code', '');
        $qrCodeBase64 = (string) data_get($payment, 'payment_method.qr_code_base64', '');
        $ticketUrl = data_get($payment, 'payment_method.ticket_url');
        $orderId = (string) $order['id'];
        $transactionId = isset($payment['id']) ? (string) $payment['id'] : null;

        $charge->update([
            'mercado_pago_order_id' => $orderId,
            'mercado_pago_transaction_id' => $transactionId,
            'payment_url' => $ticketUrl,
            'pix_qr_code' => $qrCode,
            'pix_qr_code_base64' => $qrCodeBase64,
            'pix_expires_at' => $expirationDate,
            'status' => ChargeStatus::WaitingPayment,
        ]);

        return [
            'qrCode' => $qrCode,
            'qrCodeBase64' => $qrCodeBase64,
            'expiresAt' => $expirationDate->toIso8601String(),
            'orderId' => $orderId,
            'transactionId' => $transactionId,
            'ticketUrl' => $ticketUrl,
        ];
    }

    /**
     * @return array{qrCode: string, qrCodeBase64: string, expiresAt: string, orderId: string, transactionId: string|null, ticketUrl: string|null}
     */
    public function createPixForDeposit(Deposit $deposit): array
    {
        $deposit->loadMissing(['contract.tenant', 'receiver']);

        if (in_array($deposit->status, [DepositStatus::Paid, DepositStatus::Refunded], true)) {
            throw new \InvalidArgumentException('Esta caucao nao esta em aberto.');
        }

        if ($this->hasReusableDepositPix($deposit)) {
            return [
                'qrCode' => (string) $deposit->pix_qr_code,
                'qrCodeBase64' => (string) ($deposit->pix_qr_code_base64 ?? ''),
                'expiresAt' => $deposit->pix_expires_at?->toIso8601String() ?? now()->addHour()->toIso8601String(),
                'orderId' => (string) $deposit->mercado_pago_order_id,
                'transactionId' => $deposit->mercado_pago_transaction_id,
                'ticketUrl' => $deposit->payment_url,
            ];
        }

        $accessToken = $this->ensureFreshAccessToken($deposit->receiver);
        $amount = $this->formatAmount((float) $deposit->amount);
        $expirationDate = now()->addHour();

        $tenant = $deposit->contract->tenant;
        $nameParts = explode(' ', $tenant->name, 2);

        $response = $this->http($accessToken)
            ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
            ->post(self::ORDERS_URL, [
                'type' => 'online',
                'processing_mode' => 'automatic',
                'total_amount' => $amount,
                'external_reference' => 'deposit:'.$deposit->id,
                'description' => filled($deposit->description) ? $deposit->description : "Caucao {$deposit->id}",
                'payer' => [
                    'email' => $tenant->email,
                    'first_name' => $nameParts[0] ?: $tenant->name,
                    'last_name' => $nameParts[1] ?? '-',
                    'identification' => $this->buildPayerIdentification($tenant->document),
                ],
                'transactions' => [
                    'payments' => [
                        [
                            'amount' => $amount,
                            'payment_method' => [
                                'id' => 'pix',
                                'type' => 'bank_transfer',
                            ],
                            'expiration_time' => self::PIX_EXPIRATION,
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Falha ao criar order Pix no Mercado Pago ('.$response->status().'): '.$response->body(),
            );
        }

        $order = $response->json();
        $payment = data_get($order, 'transactions.payments.0', []);
        $qrCode = (string) data_get($payment, 'payment_method.qr_code', '');
        $qrCodeBase64 = (string) data_get($payment, 'payment_method.qr_code_base64', '');
        $ticketUrl = data_get($payment, 'payment_method.ticket_url');
        $orderId = (string) $order['id'];
        $transactionId = isset($payment['id']) ? (string) $payment['id'] : null;

        $deposit->update([
            'mercado_pago_order_id' => $orderId,
            'mercado_pago_transaction_id' => $transactionId,
            'payment_url' => $ticketUrl,
            'pix_qr_code' => $qrCode,
            'pix_qr_code_base64' => $qrCodeBase64,
            'pix_expires_at' => $expirationDate,
            'status' => DepositStatus::WaitingPayment,
        ]);

        return [
            'qrCode' => $qrCode,
            'qrCodeBase64' => $qrCodeBase64,
            'expiresAt' => $expirationDate->toIso8601String(),
            'orderId' => $orderId,
            'transactionId' => $transactionId,
            'ticketUrl' => $ticketUrl,
        ];
    }

    /**
     * @return array{status: string, updated: bool}
     */
    public function syncDepositPayment(Deposit $deposit): array
    {
        if (! $deposit->mercado_pago_order_id) {
            throw new \InvalidArgumentException('Essa caucao ainda nao tem uma order Pix gerada.');
        }

        if ($deposit->status === DepositStatus::Paid) {
            return ['status' => 'already_paid', 'updated' => false];
        }

        $order = $this->fetchOrderDetails($deposit->mercado_pago_order_id);

        if ($order['status'] !== 'processed' || ! $order['externalReference']) {
            return ['status' => $order['status'], 'updated' => false];
        }

        $isNew = $this->recordApprovedDepositPayment([
            'depositId' => $deposit->id,
            'externalId' => $deposit->mercado_pago_order_id,
            'amountPaid' => $order['paidAmount'],
            'netAmount' => $order['netAmount'],
            'fees' => $order['feeAmount'],
            'paidAt' => $order['paidAt'] ?? now()->toIso8601String(),
            'method' => $order['paymentMethod'],
        ]);

        return ['status' => 'processed', 'updated' => $isNew];
    }

    /**
     * @return array{status: string, updated: bool}
     */
    public function syncChargePayment(Charge $charge): array
    {
        if (! $charge->mercado_pago_order_id) {
            throw new \InvalidArgumentException('Essa cobranca ainda nao tem uma order Pix gerada.');
        }

        if ($charge->status === ChargeStatus::Paid) {
            return ['status' => 'already_paid', 'updated' => false];
        }

        $order = $this->fetchOrderDetails($charge->mercado_pago_order_id);

        if ($order['status'] !== 'processed' || ! $order['externalReference']) {
            return ['status' => $order['status'], 'updated' => false];
        }

        $isNew = $this->recordApprovedPayment([
            'chargeId' => (int) $order['externalReference'],
            'externalId' => $charge->mercado_pago_order_id,
            'amountPaid' => $order['paidAmount'],
            'netAmount' => $order['netAmount'],
            'fees' => $order['feeAmount'],
            'paidAt' => $order['paidAt'] ?? now()->toIso8601String(),
            'method' => $order['paymentMethod'],
        ]);

        return ['status' => 'processed', 'updated' => $isNew];
    }

    public function validateWebhookSignature(?string $xSignature, ?string $xRequestId, string $dataId): bool
    {
        $secret = config('services.mercadopago.webhook_secret');

        if (! $secret) {
            return true;
        }

        if (! $xSignature) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);
            if ($key) {
                $parts[trim($key)] = trim((string) $value);
            }
        }

        $ts = $parts['ts'] ?? null;
        $v1 = $parts['v1'] ?? null;

        if (! $ts || ! $v1) {
            return false;
        }

        $manifest = "id:{$dataId};request-id:".($xRequestId ?? '').";ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $v1);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{handled: bool, status?: string}
     */
    public function handleWebhookOrder(array $payload): array
    {
        $type = (string) data_get($payload, 'type', '');
        $orderId = (string) data_get($payload, 'data.id', '');

        if ($orderId === '') {
            return ['handled' => false];
        }

        if ($type !== '' && $type !== 'order') {
            return ['handled' => false, 'status' => 'ignored_type'];
        }

        $action = (string) data_get($payload, 'action', '');

        if ($action !== '' && ! in_array($action, ['order.processed', 'order.action_required'], true)) {
            return ['handled' => true, 'status' => $action];
        }

        $order = $this->fetchOrderDetails($orderId);

        if ($order['status'] !== 'processed' || ! $order['externalReference']) {
            return ['handled' => true, 'status' => $order['status']];
        }

        $reference = (string) $order['externalReference'];

        $isNew = str_starts_with($reference, 'deposit:')
            ? $this->recordApprovedDepositPayment([
                'depositId' => (int) substr($reference, strlen('deposit:')),
                'externalId' => $orderId,
                'amountPaid' => $order['paidAmount'],
                'netAmount' => $order['netAmount'],
                'fees' => $order['feeAmount'],
                'paidAt' => $order['paidAt'] ?? now()->toIso8601String(),
                'method' => $order['paymentMethod'],
            ])
            : $this->recordApprovedPayment([
                'chargeId' => (int) $reference,
                'externalId' => $orderId,
                'amountPaid' => $order['paidAmount'],
                'netAmount' => $order['netAmount'],
                'fees' => $order['feeAmount'],
                'paidAt' => $order['paidAt'] ?? now()->toIso8601String(),
                'method' => $order['paymentMethod'],
            ]);

        return ['handled' => true, 'status' => $isNew ? 'processed' : 'duplicate'];
    }

    /**
     * @param  array{chargeId: int, externalId: string, amountPaid: float, netAmount: float|null, fees: float|null, paidAt: string, method?: PaymentMethod}  $input
     */
    public function recordApprovedPayment(array $input): bool
    {
        if (Payment::query()->where('external_id', $input['externalId'])->exists()) {
            return false;
        }

        Payment::query()->create([
            'charge_id' => $input['chargeId'],
            'amount_paid' => $input['amountPaid'],
            'net_amount' => $input['netAmount'],
            'fees' => $input['fees'],
            'method' => $input['method'] ?? PaymentMethod::Pix,
            'status' => PaymentStatus::Approved,
            'paid_at' => $input['paidAt'],
            'external_id' => $input['externalId'],
        ]);

        Charge::query()->whereKey($input['chargeId'])->update(['status' => ChargeStatus::Paid]);

        return true;
    }

    /**
     * @param  array{depositId: int, externalId: string, amountPaid: float, netAmount: float|null, fees: float|null, paidAt: string, method?: PaymentMethod}  $input
     */
    public function recordApprovedDepositPayment(array $input): bool
    {
        if (Payment::query()->where('external_id', $input['externalId'])->exists()) {
            return false;
        }

        Payment::query()->create([
            'deposit_id' => $input['depositId'],
            'amount_paid' => $input['amountPaid'],
            'net_amount' => $input['netAmount'],
            'fees' => $input['fees'],
            'method' => $input['method'] ?? PaymentMethod::Pix,
            'status' => PaymentStatus::Approved,
            'paid_at' => $input['paidAt'],
            'external_id' => $input['externalId'],
        ]);

        Deposit::query()->whereKey($input['depositId'])->update([
            'status' => DepositStatus::Paid,
            'paid_at' => $input['paidAt'],
        ]);

        return true;
    }

    public function verifyConnectState(string $state): string
    {
        $decoded = base64_decode($state, true);

        if ($decoded === false) {
            throw new \InvalidArgumentException('State invalido.');
        }

        $parts = explode('.', $decoded);

        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('State invalido.');
        }

        [$receiverId, $timestampRaw, $signature] = $parts;
        $timestamp = (int) $timestampRaw;

        if (! $receiverId || ! $timestamp) {
            throw new \InvalidArgumentException('State invalido.');
        }

        $expected = hash_hmac('sha256', "{$receiverId}.{$timestampRaw}", (string) config('services.mercadopago.client_secret'));

        if (! hash_equals($expected, $signature)) {
            throw new \InvalidArgumentException('State invalido (assinatura nao confere).');
        }

        if ((int) now()->timestamp - $timestamp > self::STATE_TTL_SECONDS) {
            throw new \InvalidArgumentException('Link de conexao expirado. Gere um novo.');
        }

        return $receiverId;
    }

    public function computeCurrentAmountDue(Charge $charge): float
    {
        $charge->loadMissing('contract');

        return Money::roundCents(Finance::computeAmountDue([
            'originalAmount' => (float) $charge->original_amount,
            'dueDate' => $charge->due_date->format('Y-m-d'),
            'status' => $charge->status->value,
            'graceDays' => $charge->contract->grace_days,
            'fineRate' => (float) $charge->contract->fine_rate,
            'monthlyInterestRate' => (float) $charge->contract->monthly_interest_rate,
        ]));
    }

    /**
     * @return array{status: string, externalReference: string|null, paidAmount: float, netAmount: float|null, feeAmount: float|null, paidAt: string|null, paymentMethod: PaymentMethod, transactionId: string|null}
     */
    public function fetchOrderDetails(string $orderId): array
    {
        $accessToken = $this->getPlatformAccessToken();

        $response = $this->http($accessToken)->get(self::ORDERS_URL.'/'.$orderId);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Falha ao consultar order no Mercado Pago ('.$response->status().'): '.$response->body(),
            );
        }

        $order = $response->json();
        $payment = data_get($order, 'transactions.payments.0', []);
        $paidAmount = (float) ($order['total_paid_amount']
            ?? data_get($payment, 'paid_amount')
            ?? $order['total_amount']
            ?? 0);
        $feeAmount = $this->extractFeeAmount($order, $payment);

        return [
            'status' => (string) ($order['status'] ?? ''),
            'externalReference' => $order['external_reference'] ?? null,
            'paidAmount' => $paidAmount,
            'netAmount' => $feeAmount !== null ? round($paidAmount - $feeAmount, 2) : null,
            'feeAmount' => $feeAmount,
            'paidAt' => $order['updated_date']
                ?? $order['last_updated_date']
                ?? $order['created_date']
                ?? null,
            'paymentMethod' => $this->mapPaymentMethod(
                (string) data_get($payment, 'payment_method.id', 'pix'),
                (string) data_get($payment, 'payment_method.type', 'bank_transfer'),
            ),
            'transactionId' => isset($payment['id']) ? (string) $payment['id'] : null,
        ];
    }

    private function hasReusablePix(Charge $charge): bool
    {
        return filled($charge->mercado_pago_order_id)
            && filled($charge->pix_qr_code)
            && $charge->pix_expires_at !== null
            && $charge->pix_expires_at->isFuture();
    }

    private function hasReusableDepositPix(Deposit $deposit): bool
    {
        return filled($deposit->mercado_pago_order_id)
            && filled($deposit->pix_qr_code)
            && $deposit->pix_expires_at !== null
            && $deposit->pix_expires_at->isFuture();
    }

    private function ensureFreshAccessToken(Receiver $receiver): string
    {
        if ($receiver->mp_access_token) {
            if ($receiver->mp_live_mode === false) {
                throw new \InvalidArgumentException(
                    'Este recebedor esta conectado com credenciais de teste do Mercado Pago. A Orders API exige token de producao (APP_USR-): defina MP_SANDBOX_CONNECT=false e reconecte a conta do recebedor.',
                );
            }

            // phpstan claims mp_token_expires_at is never null here, but the
            // column is genuinely nullable (a receiver can have an access
            // token without ever having had an expiry recorded) — keep the
            // nullsafe.
            $expiresAt = (int) ($receiver->mp_token_expires_at?->timestamp ?? 0); // @phpstan-ignore nullsafe.neverNull
            $isExpiringSoon = $expiresAt - (int) now()->timestamp < self::TOKEN_REFRESH_MARGIN_SECONDS;

            if (! $isExpiringSoon) {
                return $this->assertOrdersApiAccessToken($receiver->mp_access_token);
            }

            if ($receiver->mp_refresh_token) {
                $refreshed = $this->requestToken([
                    'client_id' => config('services.mercadopago.client_id'),
                    'client_secret' => config('services.mercadopago.client_secret'),
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $receiver->mp_refresh_token,
                ]);

                $this->saveReceiverConnection($receiver, $refreshed);

                return $this->assertOrdersApiAccessToken($refreshed['access_token']);
            }

            return $this->assertOrdersApiAccessToken($receiver->mp_access_token);
        }

        $platformToken = config('services.mercadopago.access_token');

        if (filled($platformToken) && $this->allowsPlatformTokenFallback()) {
            return $this->assertOrdersApiAccessToken((string) $platformToken);
        }

        throw new \InvalidArgumentException(
            'Este recebedor ainda nao conectou a conta Mercado Pago.',
        );
    }

    private function assertOrdersApiAccessToken(string $accessToken): string
    {
        if (str_starts_with($accessToken, 'TEST-')) {
            throw new \InvalidArgumentException(
                'A Orders API do Mercado Pago nao aceita Access Tokens de teste (TEST-...). Use credenciais de producao (APP_USR-). Para sandbox, use usuarios de teste com MP_SANDBOX_CONNECT=false.',
            );
        }

        return $accessToken;
    }

    private function allowsPlatformTokenFallback(): bool
    {
        return in_array((string) config('app.env'), ['local', 'testing'], true);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{access_token: string, refresh_token?: string, expires_in: int, user_id: int, live_mode?: bool}
     */
    private function requestToken(array $body): array
    {
        $response = Http::timeout(15)
            ->connectTimeout(5)
            ->acceptJson()
            ->asJson()
            ->post(self::OAUTH_TOKEN_URL, $body);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Falha ao obter token do Mercado Pago ('.$response->status().'): '.$response->body(),
            );
        }

        return $response->json();
    }

    private function assertOAuthCredentialsConfigured(): void
    {
        if (! filled(config('services.mercadopago.client_id'))
            || ! filled(config('services.mercadopago.client_secret'))) {
            throw new \InvalidArgumentException(
                'Configure MP_CLIENT_ID e MP_CLIENT_SECRET no .env (Client Secret da aplicacao no painel do Mercado Pago).',
            );
        }
    }

    private function getPlatformAccessToken(): string
    {
        $configured = config('services.mercadopago.access_token');

        if (filled($configured)) {
            return (string) $configured;
        }

        $token = $this->requestToken([
            'client_id' => config('services.mercadopago.client_id'),
            'client_secret' => config('services.mercadopago.client_secret'),
            'grant_type' => 'client_credentials',
        ]);

        return $token['access_token'];
    }

    private function http(string $accessToken): PendingRequest
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->connectTimeout(5)
            ->retry(2, 200, throw: false);
    }

    private function signConnectState(string $receiverId): string
    {
        $timestamp = now()->timestamp;
        $payload = "{$receiverId}.{$timestamp}";
        $signature = hash_hmac('sha256', $payload, (string) config('services.mercadopago.client_secret'));

        return base64_encode("{$payload}.{$signature}");
    }

    /**
     * @param  array{access_token: string, live_mode?: bool}  $token
     */
    private function isLiveToken(array $token): bool
    {
        if (str_starts_with($token['access_token'], 'TEST-')) {
            return false;
        }

        return $token['live_mode'] ?? true;
    }

    /**
     * @return array{type: string, number: string}
     */
    private function buildPayerIdentification(string $document): array
    {
        $digits = preg_replace('/\D/', '', $document) ?? '';

        return [
            'number' => $digits,
            'type' => strlen($digits) > 11 ? 'CNPJ' : 'CPF',
        ];
    }

    private function formatAmount(float $amount): string
    {
        return Money::formatForApi($amount);
    }

    /**
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>  $payment
     */
    private function extractFeeAmount(array $order, array $payment): ?float
    {
        $marketplaceFee = data_get($order, 'marketplace_fee');

        if ($marketplaceFee !== null && $marketplaceFee !== '') {
            return (float) $marketplaceFee;
        }

        $feeDetails = data_get($payment, 'fee_details', []);

        if (is_array($feeDetails) && $feeDetails !== []) {
            return (float) collect($feeDetails)->sum('amount');
        }

        return null;
    }

    private function mapPaymentMethod(string $methodId, string $methodType): PaymentMethod
    {
        if ($methodId === 'pix' || $methodType === 'bank_transfer') {
            return PaymentMethod::Pix;
        }

        if (in_array($methodType, ['credit_card', 'debit_card'], true)) {
            return PaymentMethod::CreditCard;
        }

        return PaymentMethod::Pix;
    }
}
