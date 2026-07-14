<?php

use App\Models\User;

test('guests are redirected from home to login', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});

test('authenticated admins are redirected from home to dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertRedirect(route('dashboard'));
});

test('authenticated tenants are redirected from home to tenant portal', function () {
    $user = User::factory()->tenant()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertRedirect(route('tenant.portal'));
});

test('authenticated receivers are redirected from home to receiver portal', function () {
    $user = User::factory()->receiver()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertRedirect(route('receiver.portal'));
});
