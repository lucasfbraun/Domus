<?php

use App\Services\Finance;

test('on time charges have no fine or interest', function () {
    $amount = Finance::computeAmountDue([
        'originalAmount' => 1000,
        'dueDate' => '2026-07-13',
        'status' => 'open',
        'graceDays' => 0,
        'fineRate' => 0.02,
        'monthlyInterestRate' => 0.01,
    ], new DateTimeImmutable('2026-07-13T12:00:00-03:00'));

    expect($amount)->toBe(1000.0);
});

test('paid charges skip penalties', function () {
    $amount = Finance::computeAmountDue([
        'originalAmount' => 1000,
        'dueDate' => '2026-06-01',
        'status' => 'paid',
        'graceDays' => 0,
        'fineRate' => 0.02,
        'monthlyInterestRate' => 0.01,
    ], new DateTimeImmutable('2026-07-13T12:00:00-03:00'));

    expect($amount)->toBe(1000.0);
});

test('one day late applies fine and pro rata interest', function () {
    $amount = Finance::computeAmountDue([
        'originalAmount' => 1000,
        'dueDate' => '2026-07-12',
        'status' => 'open',
        'graceDays' => 0,
        'fineRate' => 0.02,
        'monthlyInterestRate' => 0.01,
    ], new DateTimeImmutable('2026-07-13T12:00:00-03:00'));

    expect($amount)->toBe(1000 + 20 + (1000 * 0.01 / 30));
});

test('thirty days late applies full month interest', function () {
    $amount = Finance::computeAmountDue([
        'originalAmount' => 1000,
        'dueDate' => '2026-06-13',
        'status' => 'open',
        'graceDays' => 0,
        'fineRate' => 0.02,
        'monthlyInterestRate' => 0.01,
    ], new DateTimeImmutable('2026-07-13T12:00:00-03:00'));

    expect($amount)->toBe(1030.0);
});

test('grace period delays penalties', function () {
    $withinGrace = Finance::computeAmountDue([
        'originalAmount' => 1000,
        'dueDate' => '2026-07-10',
        'status' => 'open',
        'graceDays' => 5,
        'fineRate' => 0.02,
        'monthlyInterestRate' => 0.01,
    ], new DateTimeImmutable('2026-07-13T12:00:00-03:00'));

    $afterGrace = Finance::computeAmountDue([
        'originalAmount' => 1000,
        'dueDate' => '2026-07-10',
        'status' => 'open',
        'graceDays' => 2,
        'fineRate' => 0.02,
        'monthlyInterestRate' => 0.01,
    ], new DateTimeImmutable('2026-07-13T12:00:00-03:00'));

    expect($withinGrace)->toBe(1000.0)
        ->and($afterGrace)->toBe(1000 + 20 + (1000 * 0.01 / 30));
});

test('splitByWeights equal shares sum to total', function () {
    $shares = Finance::splitByWeights(100, [
        ['key' => 'a', 'weight' => 1],
        ['key' => 'b', 'weight' => 1],
        ['key' => 'c', 'weight' => 1],
    ]);

    expect($shares['a'])->toBe(33.33)
        ->and($shares['b'])->toBe(33.33)
        ->and($shares['c'])->toBe(33.34)
        ->and(array_sum($shares))->toBe(100.0);
});

test('splitByWeights proportional by weight', function () {
    $shares = Finance::splitByWeights(300, [
        ['key' => 'a', 'weight' => 2],
        ['key' => 'b', 'weight' => 4],
    ]);

    expect($shares['a'])->toBe(100.0)
        ->and($shares['b'])->toBe(200.0);
});

test('splitByWeights with zero weights falls back to equal last remainder', function () {
    $shares = Finance::splitByWeights(100, [
        ['key' => 'a', 'weight' => 0],
        ['key' => 'b', 'weight' => 0],
    ]);

    expect(array_sum($shares))->toBe(100.0);
});
