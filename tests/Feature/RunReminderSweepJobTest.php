<?php

use App\Enums\ChargeStatus;
use App\Jobs\RunReminderSweepJob;
use App\Models\Charge;
use App\Models\Contract;
use App\Notifications\ChargeReminderNotification;
use App\Services\ReminderService;
use Illuminate\Support\Facades\Notification;

test('the job delegates to ReminderService and a due charge gets its reminder', function () {
    Notification::fake();
    $contract = Contract::factory()->active()->create();
    $charge = Charge::factory()->for($contract)->create([
        'status' => ChargeStatus::Open,
        'due_date' => now()->toDateString(),
    ]);

    (new RunReminderSweepJob)->handle(app(ReminderService::class));

    Notification::assertSentTo($contract->tenant, ChargeReminderNotification::class);
    expect($charge->fresh()->last_reminder_event)->toBe('due_day');
});
