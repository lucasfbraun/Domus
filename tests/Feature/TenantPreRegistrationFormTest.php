<?php

use App\Enums\PreRegistrationStatus;
use App\Models\TenantPreRegistration;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('a valid pending token shows the fillable form', function () {
    $preRegistration = TenantPreRegistration::factory()->create();

    $this->get(route('tenant-pre-registrations.show', $preRegistration->token))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/PreCadastro')
            ->where('status', 'fillable'));
});

test('an unknown token 404s', function () {
    $this->get(route('tenant-pre-registrations.show', 'nao-existe'))
        ->assertNotFound();
});

test('an expired token shows the expired state', function () {
    $preRegistration = TenantPreRegistration::factory()->expired()->create();

    $this->get(route('tenant-pre-registrations.show', $preRegistration->token))
        ->assertInertia(fn (Assert $page) => $page->where('status', 'expired'));
});

test('an already submitted token shows the submitted state', function () {
    $preRegistration = TenantPreRegistration::factory()->inReview()->create();

    $this->get(route('tenant-pre-registrations.show', $preRegistration->token))
        ->assertInertia(fn (Assert $page) => $page->where('status', 'submitted'));
});

test('a rejected token shows the rejected state', function () {
    $preRegistration = TenantPreRegistration::factory()->create(['status' => PreRegistrationStatus::Rejected]);

    $this->get(route('tenant-pre-registrations.show', $preRegistration->token))
        ->assertInertia(fn (Assert $page) => $page->where('status', 'rejected'));
});

test('an approved token shows the approved state', function () {
    $preRegistration = TenantPreRegistration::factory()->create(['status' => PreRegistrationStatus::Approved]);

    $this->get(route('tenant-pre-registrations.show', $preRegistration->token))
        ->assertInertia(fn (Assert $page) => $page->where('status', 'approved'));
});

test('submitting a valid pending token moves it to in review', function () {
    $preRegistration = TenantPreRegistration::factory()->create();

    $this->post(route('tenant-pre-registrations.submit', $preRegistration->token), [
        'name' => 'Maria Silva',
        'document' => '529.982.247-25',
        'email' => 'maria@example.com',
        'whatsapp' => '(11) 99999-8888',
        'resident_count' => 3,
    ])->assertRedirect(route('tenant-pre-registrations.show', $preRegistration->token));

    $preRegistration->refresh();
    expect($preRegistration->status)->toBe(PreRegistrationStatus::InReview)
        ->and($preRegistration->name)->toBe('Maria Silva')
        ->and($preRegistration->document)->toBe('52998224725')
        ->and($preRegistration->resident_count)->toBe(3);
});

test('submitting requires the required fields', function () {
    $preRegistration = TenantPreRegistration::factory()->create();

    $this->post(route('tenant-pre-registrations.submit', $preRegistration->token), [])
        ->assertSessionHasErrors(['name', 'document', 'email', 'whatsapp', 'resident_count']);

    expect($preRegistration->fresh()->status)->toBe(PreRegistrationStatus::Pending);
});

test('submitting an unknown token 404s', function () {
    $this->post(route('tenant-pre-registrations.submit', 'nao-existe'), [
        'name' => 'Maria Silva',
        'document' => '52998224725',
        'email' => 'maria@example.com',
        'whatsapp' => '5511999998888',
        'resident_count' => 1,
    ])->assertNotFound();
});

test('submitting an already submitted token fails', function () {
    $preRegistration = TenantPreRegistration::factory()->inReview()->create();

    $this->post(route('tenant-pre-registrations.submit', $preRegistration->token), [
        'name' => 'Outro Nome',
        'document' => '52998224725',
        'email' => 'outro@example.com',
        'whatsapp' => '5511999998888',
        'resident_count' => 1,
    ])->assertSessionHasErrors('form');
});

test('submitting an expired token fails', function () {
    $preRegistration = TenantPreRegistration::factory()->expired()->create();

    $this->post(route('tenant-pre-registrations.submit', $preRegistration->token), [
        'name' => 'Maria Silva',
        'document' => '52998224725',
        'email' => 'maria@example.com',
        'whatsapp' => '5511999998888',
        'resident_count' => 1,
    ])->assertSessionHasErrors('form');
});
