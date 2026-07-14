<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('tenant cannot access admin owners', function () {
    $tenantUser = User::factory()->tenant()->create();

    $this->actingAs($tenantUser)
        ->get(route('admin.owners.index'))
        ->assertForbidden();
});
