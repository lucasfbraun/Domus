<?php

use App\Models\Charge;
use App\Models\Tenant;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\ChargeReminderNotification;
use App\Notifications\Messages\WhatsAppMessage;
use App\Services\WhatsAppClient;
use Illuminate\Support\Facades\Http;

test('whatsapp channel sends text through waha client', function () {
    config([
        'services.waha.url' => 'https://waha.test',
        'services.waha.api_key' => 'test-key',
        'services.waha.session' => 'default',
    ]);

    Http::fake([
        'https://waha.test/*' => Http::response(['ok' => true], 200),
    ]);

    $tenant = Tenant::factory()->create([
        'whatsapp' => '5511999998888',
    ]);

    $notification = new ChargeReminderNotification(Charge::factory()->make(), [
        'event' => 'due_day',
        'tenantName' => $tenant->name,
        'amount' => 1500.0,
        'dueDate' => '10/07/2026',
        'paymentUrl' => 'https://example.test/portal/tenant',
        'propertyName' => 'Apartamento',
    ]);

    (new WhatsAppChannel(app(WhatsAppClient::class)))->send($tenant, $notification);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/api/sendText')
        && $request['chatId'] === '5511999998888@c.us'
        && str_contains($request['text'], $tenant->name)
        && str_contains($request['text'], 'R$ 1.500,00'));
});

test('charge reminder notification exposes whatsapp message', function () {
    $notification = new ChargeReminderNotification(Charge::factory()->make(), [
        'event' => 'before_due',
        'tenantName' => 'Maria',
        'amount' => 1000.0,
        'dueDate' => '15/07/2026',
        'paymentUrl' => 'https://example.test/portal',
        'propertyName' => 'Casa',
    ]);

    $message = $notification->toWhatsApp(Tenant::factory()->make());

    expect($message)->toBeInstanceOf(WhatsAppMessage::class)
        ->and($message->text)->toContain('Maria')
        ->and($message->text)->toContain('R$ 1.000,00');
});
