<?php

use App\Models\User;
use App\Support\Money;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('money helper formats values for display and apis', function () {
    expect(Money::roundCents(10.555))->toBe(10.56)
        ->and(Money::format(1500.5))->toBe('R$ 1.500,50')
        ->and(Money::format(null))->toBe('—')
        ->and(Money::formatForApi(1500.5))->toBe('1500.50')
        ->and(Money::inertiaConfig())->toBe([
            'currency' => 'BRL',
            'locale' => 'pt-BR',
            'decimals' => 2,
        ]);
});

test('inertia shares money config on authenticated pages', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('money.currency', 'BRL')
            ->where('money.locale', 'pt-BR')
            ->where('money.decimals', 2));
});
