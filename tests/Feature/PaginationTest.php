<?php

use App\Models\Charge;
use App\Models\Contract;
use App\Models\ContractOccurrence;
use App\Models\ContractTemplate;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Rateio;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Pagination;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('admin owners index is paginated', function () {
    $admin = User::factory()->admin()->create();
    Owner::factory()->count(13)->create();

    $this->actingAs($admin)
        ->get(route('admin.owners.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/owners/Index')
            ->has('owners.data', Pagination::PER_PAGE)
            ->where('owners.per_page', Pagination::PER_PAGE)
            ->where('owners.current_page', 1)
            ->where('owners.last_page', 2)
            ->where('owners.total', 13));
});

test('admin properties index is paginated', function () {
    $admin = User::factory()->admin()->create();
    Property::factory()->count(13)->create();

    $this->actingAs($admin)
        ->get(route('admin.properties.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('properties.data', Pagination::PER_PAGE)
            ->where('properties.per_page', Pagination::PER_PAGE)
            ->where('properties.total', 13));
});

test('admin tenants index is paginated', function () {
    $admin = User::factory()->admin()->create();
    Tenant::factory()->count(13)->create();

    $this->actingAs($admin)
        ->get(route('admin.tenants.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tenants.data', Pagination::PER_PAGE)
            ->where('tenants.per_page', Pagination::PER_PAGE)
            ->where('tenants.total', 13));
});

test('admin receivers index is paginated', function () {
    $admin = User::factory()->admin()->create();
    Receiver::factory()->count(13)->create();

    $this->actingAs($admin)
        ->get(route('admin.receivers.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('receivers.data', Pagination::PER_PAGE)
            ->where('receivers.per_page', Pagination::PER_PAGE)
            ->where('receivers.total', 13));
});

test('admin contracts index is paginated', function () {
    $admin = User::factory()->admin()->create();
    Contract::factory()->count(13)->create();

    $this->actingAs($admin)
        ->get(route('admin.contracts.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('contracts.data', Pagination::PER_PAGE)
            ->where('contracts.per_page', Pagination::PER_PAGE)
            ->where('contracts.total', 13));
});

test('admin charges index is paginated', function () {
    $admin = User::factory()->admin()->create();
    Charge::factory()->count(13)->create();

    $this->actingAs($admin)
        ->get(route('admin.charges.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('charges.data', Pagination::PER_PAGE)
            ->where('charges.per_page', Pagination::PER_PAGE)
            ->where('charges.total', 13));
});

test('admin rateios index is paginated', function () {
    $admin = User::factory()->admin()->create();
    Rateio::factory()->count(13)->create();

    $this->actingAs($admin)
        ->get(route('admin.rateios.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('rateios.data', Pagination::PER_PAGE)
            ->where('rateios.per_page', Pagination::PER_PAGE)
            ->where('rateios.total', 13));
});

test('admin templates index is paginated', function () {
    $admin = User::factory()->admin()->create();
    ContractTemplate::factory()->count(13)->create();

    $this->actingAs($admin)
        ->get(route('admin.templates.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('templates.data', Pagination::PER_PAGE)
            ->where('templates.per_page', Pagination::PER_PAGE)
            ->where('templates.total', 13));
});

test('admin occurrences index is paginated', function () {
    $admin = User::factory()->admin()->create();
    ContractOccurrence::factory()->count(13)->create();

    $this->actingAs($admin)
        ->get(route('admin.occurrences.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('occurrences.data', Pagination::PER_PAGE)
            ->where('occurrences.per_page', Pagination::PER_PAGE)
            ->where('occurrences.total', 13));
});

test('admin admins index is paginated', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->count(13)->create();

    $this->actingAs($admin)
        ->get(route('admin.admins.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('admins.data', Pagination::PER_PAGE)
            ->where('admins.per_page', Pagination::PER_PAGE)
            ->where('admins.total', 14));
});

test('admin can navigate to the second page of a listing', function () {
    $admin = User::factory()->admin()->create();
    Owner::factory()->count(13)->create();

    $this->actingAs($admin)
        ->get(route('admin.owners.index', ['page' => 2]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('owners.data', 1)
            ->where('owners.current_page', 2)
            ->where('owners.last_page', 2)
            ->where('owners.total', 13));
});

test('tenant portal paginates contracts and charges independently', function () {
    $tenantUser = User::factory()->tenant()->create();
    $tenant = Tenant::factory()->create(['user_id' => $tenantUser->id]);

    $contracts = Contract::factory()->active()->for($tenant)->count(13)->create();

    foreach ($contracts as $contract) {
        Charge::factory()->open()->for($contract)->for($contract->receiver)->count(1)->create();
    }

    Charge::factory()
        ->open()
        ->for($contracts->first())
        ->for($contracts->first()->receiver)
        ->count(12)
        ->create();

    $this->actingAs($tenantUser)
        ->get(route('tenant.portal', ['contracts' => 2, 'charges' => 1]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('contracts.data', 1)
            ->where('contracts.current_page', 2)
            ->where('contracts.per_page', Pagination::PER_PAGE)
            ->where('contracts.total', 13)
            ->has('charges.data', Pagination::PER_PAGE)
            ->where('charges.current_page', 1)
            ->where('charges.per_page', Pagination::PER_PAGE)
            ->where('charges.total', 25));
});
