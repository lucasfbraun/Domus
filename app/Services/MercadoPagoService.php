<?php

namespace App\Services;

use App\Enums\ChargeStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Charge;
use App\Models\Payment;
use App\Models\Receiver;
use App\Support\Money;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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

        $isNew = $this->recordApprovedPayment([
            'chargeId' => (int) $order['externalReference'],
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

        if (now()->timestamp - $timestamp > self::STATE_TTL_SECONDS) {
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

    private function ensureFreshAccessToken(Receiver $receiver): string
    {
        if ($receiver->mp_access_token) {
            $expiresAt = $receiver->mp_token_expires_at?->timestamp ?? 0;
            $isExpiringSoon = $expiresAt - now()->timestamp < self::TOKEN_REFRESH_MARGIN_SECONDS;

            if (! $isExpiringSoon) {
                return $receiver->mp_access_token;
            }

            if ($receiver->mp_refresh_token) {
                $refreshed = $this->requestToken([
                    'client_id' => config('services.mercadopago.client_id'),
                    'client_secret' => config('services.mercadopago.client_secret'),
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $receiver->mp_refresh_token,
                ]);

                $this->saveReceiverConnection($receiver, $refreshed);

                return $refreshed['access_token'];
            }

            return $receiver->mp_access_token;
        }

        $platformToken = config('services.mercadopago.access_token');

        if (filled($platformToken)) {
            return (string) $platformToken;
        }

        throw new \InvalidArgumentException(
            'Este recebedor ainda nao conectou a conta Mercado Pago e nao ha MP_ACCESS_TOKEN configurado.',
        );
    }

    /**
     * @return array{access_token: string, refresh_token?: string, expires_in: int, user_id: int, live_mode?: bool}
     */
    private function requestToken(array $body): array
    {
        $response = Http::timeout(15)
            ->connectTimeout(5)
            ->post(self::OAUTH_TOKEN_URL, $body);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Falha ao obter token do Mercado Pago ('.$response->status().'): '.$response->body(),
            );
        }

        return $response->json();
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
