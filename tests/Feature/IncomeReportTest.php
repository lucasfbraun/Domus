<?php

use App\Enums\PaymentStatus;
use App\Models\Contract;
use App\Models\Deposit;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Receiver;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('year only filter brings every month of that year', function () {
    $admin = User::factory()->admin()->create();

    createApprovedRentPayment([], ['paid_at' => '2026-01-15', 'net_amount' => 900]);
    createApprovedRentPayment([], ['paid_at' => '2026-03-10', 'net_amount' => 900]);

    $this->actingAs($admin)
        ->get(route('admin.income-report.index', ['year' => 2026]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/reports/IncomeReport')
            ->has('months', 12)
            ->where('months.0.reference', '2026-01')
            ->where('months.0.total', amountEquals(900.0))
            ->where('months.2.reference', '2026-03')
            ->where('months.2.total', amountEquals(900.0))
            ->where('months.1.total', amountEquals(0.0))
            ->where('total', amountEquals(1800.0)));
});

test('year and month filter narrows to a single month', function () {
    $admin = User::factory()->admin()->create();

    createApprovedRentPayment([], ['paid_at' => '2026-01-15', 'net_amount' => 900]);
    createApprovedRentPayment([], ['paid_at' => '2026-03-10', 'net_amount' => 700]);

    $this->actingAs($admin)
        ->get(route('admin.income-report.index', ['year' => 2026, 'month' => 1]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/reports/IncomeReport')
            ->has('months', 1)
            ->where('months.0.reference', '2026-01')
            ->where('months.0.total', amountEquals(900.0))
            ->where('total', amountEquals(900.0)));
});

test('deposit payments are excluded, only rent charge payments count', function () {
    $admin = User::factory()->admin()->create();

    createApprovedRentPayment([], ['paid_at' => '2026-05-05', 'net_amount' => 900]);

    $deposit = Deposit::factory()->create();
    Payment::factory()->for($deposit)->create([
        'charge_id' => null,
        'status' => PaymentStatus::Approved,
        'net_amount' => 5000,
        'paid_at' => '2026-05-06',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.income-report.index', ['year' => 2026]))
        ->assertInertia(fn ($page) => $page
            ->where('total', amountEquals(900.0))
            ->has('payments', 1));
});

test('non approved payments are excluded', function () {
    $admin = User::factory()->admin()->create();

    createApprovedRentPayment([], ['paid_at' => '2026-06-01', 'net_amount' => 900, 'status' => PaymentStatus::Approved]);
    createApprovedRentPayment([], ['paid_at' => '2026-06-02', 'net_amount' => 900, 'status' => PaymentStatus::Pending]);
    createApprovedRentPayment([], ['paid_at' => '2026-06-03', 'net_amount' => 900, 'status' => PaymentStatus::Rejected]);

    $this->actingAs($admin)
        ->get(route('admin.income-report.index', ['year' => 2026]))
        ->assertInertia(fn ($page) => $page
            ->where('total', amountEquals(900.0))
            ->has('payments', 1));
});

test('receiver_id filter scopes income to that receiver only', function () {
    $admin = User::factory()->admin()->create();
    $receiverA = Receiver::factory()->create();
    $receiverB = Receiver::factory()->create();

    createApprovedRentPayment(['receiver_id' => $receiverA->id], ['paid_at' => '2026-02-01', 'net_amount' => 500]);
    createApprovedRentPayment(['receiver_id' => $receiverB->id], ['paid_at' => '2026-02-02', 'net_amount' => 700]);

    $this->actingAs($admin)
        ->get(route('admin.income-report.index', ['year' => 2026, 'receiver_id' => $receiverA->id]))
        ->assertInertia(fn ($page) => $page
            ->where('total', amountEquals(500.0))
            ->has('payments', 1));
});

test('owner_id filter scopes income through property ownership', function () {
    $admin = User::factory()->admin()->create();

    $ownerA = Owner::factory()->create();
    $propertyA = Property::factory()->create();
    $propertyA->owners()->attach($ownerA->id);
    $contractA = Contract::factory()->for($propertyA)->create();

    $ownerB = Owner::factory()->create();
    $propertyB = Property::factory()->create();
    $propertyB->owners()->attach($ownerB->id);
    $contractB = Contract::factory()->for($propertyB)->create();

    createApprovedRentPayment(['contract_id' => $contractA->id], ['paid_at' => '2026-04-01', 'net_amount' => 600]);
    createApprovedRentPayment(['contract_id' => $contractB->id], ['paid_at' => '2026-04-02', 'net_amount' => 800]);

    $this->actingAs($admin)
        ->get(route('admin.income-report.index', ['year' => 2026, 'owner_id' => $ownerA->id]))
        ->assertInertia(fn ($page) => $page
            ->where('total', amountEquals(600.0))
            ->has('payments', 1));
});

test('pdf endpoint downloads a pdf for the filtered period', function () {
    $admin = User::factory()->admin()->create();

    createApprovedRentPayment([], ['paid_at' => '2026-07-01', 'net_amount' => 900]);

    $response = $this->actingAs($admin)
        ->get(route('admin.income-report.pdf', ['year' => 2026, 'month' => 7]))
        ->assertSuccessful();

    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('non admin cannot access the income report', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('admin.income-report.index'))
        ->assertForbidden();
});
