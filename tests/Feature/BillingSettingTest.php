<?php

use App\Models\BillingSetting;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('non admin cannot access the billing settings page', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('admin.billing-settings.edit'))
        ->assertForbidden();
});

test('admin sees the default generation day when none was configured yet', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.billing-settings.edit'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/BillingSettings')
            ->where('generation_day', BillingSetting::DEFAULT_GENERATION_DAY));
});

test('admin can update the charge generation day', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.billing-settings.update'), ['generation_day' => 15])
        ->assertRedirect();

    expect(BillingSetting::current()->generation_day)->toBe(15);
});

test('generation day must be between 1 and 28', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.billing-settings.update'), ['generation_day' => 29])
        ->assertSessionHasErrors('generation_day');

    $this->actingAs($admin)
        ->put(route('admin.billing-settings.update'), ['generation_day' => 0])
        ->assertSessionHasErrors('generation_day');
});
