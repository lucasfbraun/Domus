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

test('formats a stored (DDI-prefixed) mobile number for display without the DDI', function () {
    expect(BrazilianPhone::format('5511988880000'))->toBe('(11) 98888-0000');
});

test('formats a stored (DDI-prefixed) landline number for display without the DDI', function () {
    expect(BrazilianPhone::format('551133330000'))->toBe('(11) 3333-0000');
});

test('format returns an empty string for empty input', function () {
    expect(BrazilianPhone::format(''))->toBe('')
        ->and(BrazilianPhone::format(null))->toBe('');
});
