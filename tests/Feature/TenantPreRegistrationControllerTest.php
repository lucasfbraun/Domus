<?php

use App\Enums\PreRegistrationStatus;
use App\Models\Tenant;
use App\Models\TenantPreRegistration;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non admin cannot access the pre-registrations page', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('admin.tenant-pre-registrations.index'))
        ->assertForbidden();
});

test('admin sees the pre-registrations list with a shareable link for pending invites', function () {
    $admin = User::factory()->admin()->create();
    TenantPreRegistration::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.tenant-pre-registrations.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/tenant-pre-registrations/Index')
            ->has('preRegistrations.data', 1)
            ->where('preRegistrations.data.0.status', 'pending')
            ->has('preRegistrations.data.0.link'));
});

test('an in-review pre-registration has no shareable link', function () {
    $admin = User::factory()->admin()->create();
    TenantPreRegistration::factory()->inReview()->create();

    $this->actingAs($admin)
        ->get(route('admin.tenant-pre-registrations.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('preRegistrations.data.0.link', null));
});

test('admin can generate an invite', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.tenant-pre-registrations.store'))
        ->assertRedirect();

    expect(TenantPreRegistration::query()->count())->toBe(1)
        ->and(TenantPreRegistration::query()->first()->status)->toBe(PreRegistrationStatus::Pending);
});

test('non admin cannot generate an invite', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->post(route('admin.tenant-pre-registrations.store'))
        ->assertForbidden();

    expect(TenantPreRegistration::query()->count())->toBe(0);
});

test('admin can approve an in-review pre-registration', function () {
    $admin = User::factory()->admin()->create();
    $preRegistration = TenantPreRegistration::factory()->inReview()->create(['name' => 'Maria Silva']);

    $this->actingAs($admin)
        ->post(route('admin.tenant-pre-registrations.approve', $preRegistration))
        ->assertRedirect();

    expect(Tenant::query()->where('name', 'Maria Silva')->exists())->toBeTrue()
        ->and($preRegistration->fresh()->status)->toBe(PreRegistrationStatus::Approved);
});

test('approving a pre-registration that is not in review fails with an error', function () {
    $admin = User::factory()->admin()->create();
    $preRegistration = TenantPreRegistration::factory()->create();

    $this->actingAs($admin)
        ->from(route('admin.tenant-pre-registrations.index'))
        ->post(route('admin.tenant-pre-registrations.approve', $preRegistration))
        ->assertRedirect(route('admin.tenant-pre-registrations.index'))
        ->assertSessionHasErrors('approve');
});

test('non admin cannot approve a pre-registration', function () {
    $tenantUser = User::factory()->tenant()->create();
    $preRegistration = TenantPreRegistration::factory()->inReview()->create();

    $this->actingAs($tenantUser)
        ->post(route('admin.tenant-pre-registrations.approve', $preRegistration))
        ->assertForbidden();
});

test('admin can reject an in-review pre-registration with a note', function () {
    $admin = User::factory()->admin()->create();
    $preRegistration = TenantPreRegistration::factory()->inReview()->create();

    $this->actingAs($admin)
        ->post(route('admin.tenant-pre-registrations.reject', $preRegistration), ['note' => 'CPF invalido'])
        ->assertRedirect();

    expect($preRegistration->fresh()->status)->toBe(PreRegistrationStatus::Rejected)
        ->and($preRegistration->fresh()->rejection_note)->toBe('CPF invalido');
});

test('non admin cannot reject a pre-registration', function () {
    $tenantUser = User::factory()->tenant()->create();
    $preRegistration = TenantPreRegistration::factory()->inReview()->create();

    $this->actingAs($tenantUser)
        ->post(route('admin.tenant-pre-registrations.reject', $preRegistration))
        ->assertForbidden();
});
