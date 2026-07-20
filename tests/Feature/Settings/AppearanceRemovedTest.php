<?php

use App\Models\User;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

test('appearance settings page is no longer available', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/appearance')
        ->assertNotFound();
});

test('appearance route name is not registered', function () {
    expect(fn () => route('appearance.edit'))->toThrow(RouteNotFoundException::class);
});
