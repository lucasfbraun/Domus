<?php

use App\Support\BrazilianDocument;

test('strips document formatting to digits only', function () {
    expect(BrazilianDocument::digits('529.982.247-25'))->toBe('52998224725')
        ->and(BrazilianDocument::digits('11.222.333/0001-81'))->toBe('11222333000181');
});
