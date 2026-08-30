<?php

use App\Support\BrazilianDocument;

test('strips document formatting to digits only', function () {
    expect(BrazilianDocument::digits('529.982.247-25'))->toBe('52998224725')
        ->and(BrazilianDocument::digits('11.222.333/0001-81'))->toBe('11222333000181');
});

test('formats an 11-digit document as CPF', function () {
    expect(BrazilianDocument::format('52998224725'))->toBe('529.982.247-25');
});

test('formats a 14-digit document as CNPJ', function () {
    expect(BrazilianDocument::format('11222333000181'))->toBe('11.222.333/0001-81');
});

test('format is idempotent on an already-formatted document', function () {
    expect(BrazilianDocument::format('529.982.247-25'))->toBe('529.982.247-25');
});

test('format returns an empty string for empty input', function () {
    expect(BrazilianDocument::format(''))->toBe('')
        ->and(BrazilianDocument::format(null))->toBe('');
});
