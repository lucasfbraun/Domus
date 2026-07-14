<?php

use App\Services\BillingCycle;

test('daysInMonth handles leap and short months', function () {
    expect(BillingCycle::daysInMonth(2026, 2))->toBe(28)
        ->and(BillingCycle::daysInMonth(2024, 2))->toBe(29)
        ->and(BillingCycle::daysInMonth(2026, 4))->toBe(30)
        ->and(BillingCycle::daysInMonth(2026, 12))->toBe(31);
});

test('resolveBillingCycleDueDate uses due later this month', function () {
    $result = BillingCycle::resolveBillingCycleDueDate(15, '2026-07-13');

    expect($result['dueDateIso'])->toBe('2026-07-15')
        ->and($result['daysUntilDue'])->toBe(2);
});

test('resolveBillingCycleDueDate keeps recently passed due', function () {
    $result = BillingCycle::resolveBillingCycleDueDate(10, '2026-07-13');

    expect($result['dueDateIso'])->toBe('2026-07-10')
        ->and($result['daysUntilDue'])->toBe(-3);
});

test('resolveBillingCycleDueDate rolls forward when stale', function () {
    $result = BillingCycle::resolveBillingCycleDueDate(1, '2026-07-13');

    expect($result['dueDateIso'])->toBe('2026-08-01');
});

test('resolveBillingCycleDueDate clamps due day in short month', function () {
    $result = BillingCycle::resolveBillingCycleDueDate(31, '2026-02-10');

    expect($result['dueDateIso'])->toBe('2026-02-28');
});

test('resolveBillingCycleDueDate rolls year', function () {
    $result = BillingCycle::resolveBillingCycleDueDate(1, '2026-12-20');

    expect($result['dueDateIso'])->toBe('2027-01-01');
});

test('formatReference uses portuguese month', function () {
    expect(BillingCycle::formatReference('2026-07-15'))->toBe('Julho/2026')
        ->and(BillingCycle::formatReference('2026-01-05'))->toBe('Janeiro/2026');
});
