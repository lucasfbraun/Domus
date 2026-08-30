<?php

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ChargeReminderNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin can generate a charge for a contract', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create(['monthly_rent' => 1500, 'due_day' => 10]);

    $this->actingAs($admin)
        ->post(route('admin.charges.generate', $contract))
        ->assertRedirect();

    expect(Charge::query()->where('contract_id', $contract->id)->exists())->toBeTrue();
});

test('admin generating a charge for a contract that is already paid this cycle gets an already-paid toast', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create(['monthly_rent' => 1500, 'due_day' => 10]);

    $this->actingAs($admin)->post(route('admin.charges.generate', $contract));
    $charge = Charge::query()->where('contract_id', $contract->id)->first();
    $charge->update(['status' => ChargeStatus::Paid]);

    $this->actingAs($admin)
        ->post(route('admin.charges.generate', $contract))
        ->assertRedirect();

    expect(Charge::query()->where('contract_id', $contract->id)->count())->toBe(1)
        ->and(session('inertia.flash_data')['toast']['message'])->toContain('ja paga');
});

test('non admin cannot generate a charge', function () {
    $tenantUser = User::factory()->tenant()->create();
    $contract = Contract::factory()->active()->create();

    $this->actingAs($tenantUser)
        ->post(route('admin.charges.generate', $contract))
        ->assertForbidden();
});

test('admin can send a manual reminder for a charge', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create();
    $charge = Charge::factory()->for($contract)->for($contract->receiver)->create([
        'status' => ChargeStatus::Open,
        'due_date' => now()->toDateString(),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.charges.reminder', $charge))
        ->assertRedirect();

    Notification::assertSentTo($contract->tenant, ChargeReminderNotification::class);
    expect($charge->fresh()->last_reminder_event)->not->toBeNull();
});

test('even the owning tenant cannot send a reminder, since this endpoint is admin-only by route', function () {
    // ChargePolicy::update() itself would allow the owning tenant, but
    // admin.charges.reminder only sits behind the role:admin route group
    // (unlike charges.pix/charges.sync, which use role:admin|tenant) — so
    // it's unreachable for a tenant regardless of what the policy permits.
    $tenantUser = User::factory()->tenant()->create();
    $tenant = Tenant::factory()->create(['user_id' => $tenantUser->id]);
    $contract = Contract::factory()->active()->for($tenant)->create();
    $charge = Charge::factory()->for($contract)->for($contract->receiver)->create([
        'status' => ChargeStatus::Open,
        'due_date' => now()->toDateString(),
    ]);

    $this->actingAs($tenantUser)
        ->post(route('admin.charges.reminder', $charge))
        ->assertForbidden();
});

test('a tenant on a different contract cannot send a reminder for someone elses charge', function () {
    $tenantUser = User::factory()->tenant()->create();
    Tenant::factory()->create(['user_id' => $tenantUser->id]);
    $otherContract = Contract::factory()->active()->create();
    $charge = Charge::factory()->for($otherContract)->for($otherContract->receiver)->create();

    $this->actingAs($tenantUser)
        ->post(route('admin.charges.reminder', $charge))
        ->assertForbidden();
});

test('a receiver cannot send a reminder even for a charge they receive', function () {
    $receiverUser = User::factory()->receiver()->create();
    $receiver = Receiver::factory()->create(['user_id' => $receiverUser->id]);
    $contract = Contract::factory()->active()->for($receiver)->create();
    $charge = Charge::factory()->for($contract)->for($receiver)->create();

    $this->actingAs($receiverUser)
        ->post(route('admin.charges.reminder', $charge))
        ->assertForbidden();
});
