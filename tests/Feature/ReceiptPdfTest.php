<?php

use App\Models\Charge;
use App\Models\Contract;
use App\Support\Money;

test('receipt shows a single amount line when there is no rateio', function () {
    $contract = Contract::factory()->active()->create();
    $charge = Charge::factory()->for($contract)->create([
        'original_amount' => 1500,
        'rateio_amount' => 0,
    ]);

    $html = view('pdf.receipt', ['charge' => $charge, 'contract' => $contract, 'payment' => null])->render();

    expect($html)->toContain(Money::format(1500))
        ->and($html)->not->toContain('Rateio');
});

test('receipt discriminates rent and rateio when a rateio is included', function () {
    $contract = Contract::factory()->active()->create();
    $charge = Charge::factory()->for($contract)->create([
        'original_amount' => 1800,
        'rateio_amount' => 300,
    ]);

    $html = view('pdf.receipt', ['charge' => $charge, 'contract' => $contract, 'payment' => null])->render();

    expect($html)->toContain('Aluguel')
        ->and($html)->toContain(Money::format(1500))
        ->and($html)->toContain('Rateio')
        ->and($html)->toContain(Money::format(300))
        ->and($html)->toContain(Money::format(1800));
});
