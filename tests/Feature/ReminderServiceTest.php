<?php

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\Contract;
use App\Notifications\ChargeReminderNotification;
use App\Notifications\ContractExpiringNotification;
use App\Notifications\PaymentConfirmedNotification;
use App\Services\ReminderService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->service = app(ReminderService::class);
});

function chargeDueIn(int $days, ChargeStatus $status = ChargeStatus::Open): Charge
{
    $contract = Contract::factory()->active()->create();

    return Charge::factory()->for($contract)->create([
        'status' => $status,
        'original_amount' => 1500,
        'due_date' => now()->addDays($days)->toDateString(),
    ]);
}

// sendChargeReminder

test('sendChargeReminder sends a before_due reminder and stamps the charge', function () {
    Notification::fake();
    $charge = chargeDueIn(5);

    $result = $this->service->sendChargeReminder($charge);

    expect($result['event'])->toBe('before_due');
    Notification::assertSentTo($charge->contract->tenant, ChargeReminderNotification::class);
    expect($charge->fresh()->last_reminder_event)->toBe('before_due')
        ->and($charge->fresh()->last_reminder_sent_at)->not->toBeNull();
});

test('sendChargeReminder sends a due_day reminder when the charge is due today', function () {
    Notification::fake();
    $charge = chargeDueIn(0);

    $result = $this->service->sendChargeReminder($charge);

    expect($result['event'])->toBe('due_day');
});

test('sendChargeReminder sends an after_due reminder with days late when overdue', function () {
    Notification::fake();
    $charge = chargeDueIn(-4, ChargeStatus::Overdue);

    $result = $this->service->sendChargeReminder($charge);

    expect($result['event'])->toBe('after_due');
    Notification::assertSentTo(
        $charge->contract->tenant,
        function (ChargeReminderNotification $notification) {
            return $notification->context['daysLate'] === 4;
        },
    );
});

test('sendChargeReminder throws when the tenant has no email or whatsapp', function () {
    $charge = chargeDueIn(5);
    $charge->contract->tenant->update(['email' => '', 'whatsapp' => '']);

    $this->service->sendChargeReminder($charge);
})->throws(InvalidArgumentException::class);

// runReminderSweep

test('runReminderSweep sends the before_due reminder exactly 5 days ahead', function () {
    Notification::fake();
    $charge = chargeDueIn(5);

    $result = $this->service->runReminderSweep();

    expect($result['sent'])->toBe(1)
        ->and($charge->fresh()->last_reminder_event)->toBe('before_due');
});

test('runReminderSweep skips a charge that is not in any reminder window', function () {
    Notification::fake();
    chargeDueIn(3);

    $result = $this->service->runReminderSweep();

    expect($result['sent'])->toBe(0)
        ->and($result['skipped'])->toBe(1);
});

test('runReminderSweep does not resend the same before_due reminder twice', function () {
    Notification::fake();
    $charge = chargeDueIn(5);
    $charge->update(['last_reminder_event' => 'before_due', 'last_reminder_sent_at' => now()]);

    $result = $this->service->runReminderSweep();

    expect($result['sent'])->toBe(0)
        ->and($result['skipped'])->toBe(1);
    Notification::assertNothingSent();
});

test('runReminderSweep does not resend an after_due reminder before the resend window passes', function () {
    Notification::fake();
    $charge = chargeDueIn(-1, ChargeStatus::Overdue);
    $charge->update(['last_reminder_event' => 'after_due', 'last_reminder_sent_at' => now()->subDay()]);

    $result = $this->service->runReminderSweep();

    expect($result['sent'])->toBe(0)
        ->and($result['skipped'])->toBe(1);
});

test('runReminderSweep resends an after_due reminder once the resend window has passed', function () {
    Notification::fake();
    $charge = chargeDueIn(-1, ChargeStatus::Overdue);
    $charge->update(['last_reminder_event' => 'after_due', 'last_reminder_sent_at' => now()->subDays(4)]);

    $result = $this->service->runReminderSweep();

    expect($result['sent'])->toBe(1);
});

test('runReminderSweep counts a failure without stopping the rest of the batch', function () {
    Notification::fake();
    $failing = chargeDueIn(0);
    $failing->contract->tenant->update(['email' => '', 'whatsapp' => '']);
    $succeeding = chargeDueIn(0);

    $result = $this->service->runReminderSweep();

    expect($result['failed'])->toBe(1)
        ->and($result['sent'])->toBe(1);
    expect($succeeding->fresh()->last_reminder_event)->toBe('due_day')
        ->and($failing->fresh()->last_reminder_event)->toBeNull();
});

test('runReminderSweep marks overdue charges before sweeping reminders', function () {
    Notification::fake();
    $charge = Charge::factory()->for(Contract::factory()->active())->create([
        'status' => ChargeStatus::Open,
        'due_date' => now()->subDays(5)->toDateString(),
    ]);

    $this->service->runReminderSweep();

    expect($charge->fresh()->status)->toBe(ChargeStatus::Overdue);
});

// sendContractExpiringReminder

test('sendContractExpiringReminder notifies the tenant and stamps the contract', function () {
    Notification::fake();
    $contract = Contract::factory()->active()->create(['ends_at' => now()->addDays(10)]);

    $sent = $this->service->sendContractExpiringReminder($contract);

    expect($sent)->toBeTrue();
    Notification::assertSentTo($contract->tenant, ContractExpiringNotification::class);
    expect($contract->fresh()->expiring_reminder_sent_at)->not->toBeNull();
});

test('sendContractExpiringReminder does not resend once already sent', function () {
    Notification::fake();
    $contract = Contract::factory()->active()->create([
        'ends_at' => now()->addDays(10),
        'expiring_reminder_sent_at' => now()->subDay(),
    ]);

    $sent = $this->service->sendContractExpiringReminder($contract);

    expect($sent)->toBeFalse();
    Notification::assertNothingSent();
});

test('sendContractExpiringReminder does nothing when the tenant has no contact info', function () {
    Notification::fake();
    $contract = Contract::factory()->active()->create(['ends_at' => now()->addDays(10)]);
    $contract->tenant->update(['email' => '', 'whatsapp' => '']);

    $sent = $this->service->sendContractExpiringReminder($contract);

    expect($sent)->toBeFalse();
});

test('sendPaymentConfirmedReminder notifies tenant and receiver and stamps the charge', function () {
    Notification::fake();
    $contract = Contract::factory()->active()->create();
    $charge = Charge::factory()->for($contract)->for($contract->receiver)->create([
        'status' => ChargeStatus::Paid,
        'original_amount' => 1500,
        'due_date' => now()->toDateString(),
    ]);

    $this->service->sendPaymentConfirmedReminder($charge);

    Notification::assertSentTo($contract->tenant, PaymentConfirmedNotification::class);
    Notification::assertSentTo($contract->receiver, PaymentConfirmedNotification::class);
    expect($charge->fresh()->last_reminder_event)->toBe('payment_confirmed');
});
