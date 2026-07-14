<?php

use App\Models\Charge;
use App\Models\Contract;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\PaymentConfirmedNotification;
use App\Services\ReminderService;
use Illuminate\Support\Facades\Notification;

test('payment confirmed notification uses mail and whatsapp for tenant', function () {
    $tenant = Tenant::factory()->make([
        'email' => 'tenant@example.com',
        'whatsapp' => '5511999998888',
    ]);
    $charge = Charge::factory()->make();

    $channels = (new PaymentConfirmedNotification($charge))->via($tenant);

    expect($channels)->toContain('mail')
        ->and($channels)->toContain(WhatsAppChannel::class);
});

test('payment confirmed notification uses only mail for receiver', function () {
    $receiver = Receiver::factory()->make([
        'email' => 'receiver@example.com',
    ]);
    $charge = Charge::factory()->make();

    $channels = (new PaymentConfirmedNotification($charge))->via($receiver);

    expect($channels)->toBe(['mail']);
});

test('reminder service notifies tenant and receiver on payment confirmed', function () {
    Notification::fake();

    $tenant = Tenant::factory()->create([
        'email' => 'tenant@example.com',
        'whatsapp' => '5511999998888',
    ]);
    $receiver = Receiver::factory()->create(['email' => 'receiver@example.com']);
    $contract = Contract::factory()->for($tenant)->for($receiver)->create();
    $charge = Charge::factory()->for($contract)->for($receiver)->create();

    app(ReminderService::class)->sendPaymentConfirmedReminder($charge);

    Notification::assertSentTo($tenant, PaymentConfirmedNotification::class);
    Notification::assertSentTo($receiver, PaymentConfirmedNotification::class);

    expect($charge->fresh()->last_reminder_event)->toBe('payment_confirmed');
});

test('payment confirmed mail content includes property name', function () {
    $contract = Contract::factory()->create();
    $charge = Charge::factory()->for($contract)->create([
        'original_amount' => 1500,
    ]);
    $charge->load('contract.property');

    $mail = (new PaymentConfirmedNotification($charge))->toMail($contract->tenant);

    expect($mail->subject)->toBe('Pagamento confirmado: '.$charge->contract->property->name);
});
