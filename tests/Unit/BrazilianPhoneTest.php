<?php

use App\Support\BrazilianPhone;

test('normalizes phone with mask and adds ddi', function () {
    expect(BrazilianPhone::normalize('(11) 98888-0000'))->toBe('5511988880000');
});

test('keeps existing ddi when already present', function () {
    expect(BrazilianPhone::normalize('+55 11 98888-0000'))->toBe('5511988880000');
});

test('returns null for empty phone', function () {
    expect(BrazilianPhone::normalize(''))->toBeNull()
        ->and(BrazilianPhone::normalize(null))->toBeNull();
});
