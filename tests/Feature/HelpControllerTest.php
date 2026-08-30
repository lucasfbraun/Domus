<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non admin cannot search the help content', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->getJson(route('admin.help.search', ['q' => 'rateio']))
        ->assertForbidden();
});

test('admin can search and find a matching help entry by keyword', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->getJson(route('admin.help.search', ['q' => 'rateio']))
        ->assertSuccessful();

    $results = $response->json('results');

    expect($results)->not->toBeEmpty()
        ->and(collect($results)->pluck('id'))->toContain('rateio');
});

test('admin can find an entry by a word from its title', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->getJson(route('admin.help.search', ['q' => 'assinatura']))
        ->assertSuccessful();

    expect(collect($response->json('results'))->pluck('id'))->toContain('assinatura');
});

test('an empty query returns no results', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->getJson(route('admin.help.search', ['q' => '']))
        ->assertSuccessful();

    expect($response->json('results'))->toBe([]);
});

test('a query matching nothing returns no results', function () {
    // Picked to avoid accidentally embedding a real Portuguese word as a
    // substring (e.g. a naive "nao-existe" contains "nao", which matches
    // for real via a normalized substring check against the answer text).
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->getJson(route('admin.help.search', ['q' => 'qwqwqwqwqw zxzxzxzxzx']))
        ->assertSuccessful();

    expect($response->json('results'))->toBe([]);
});
