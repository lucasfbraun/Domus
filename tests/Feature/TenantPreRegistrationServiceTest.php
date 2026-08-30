<?php

use App\Enums\PreRegistrationStatus;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantPreRegistration;
use App\Models\User;
use App\Services\TenantPreRegistrationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->service = app(TenantPreRegistrationService::class);
});

test('invite creates a pending pre-registration with a unique token and an expiry', function () {
    $preRegistration = $this->service->invite();

    expect($preRegistration->status)->toBe(PreRegistrationStatus::Pending)
        ->and($preRegistration->token)->not->toBeEmpty()
        ->and($preRegistration->name)->toBeNull()
        ->and($preRegistration->invited_at)->not->toBeNull()
        ->and($preRegistration->expires_at->isFuture())->toBeTrue();
});

test('two invites never share a token', function () {
    $first = $this->service->invite();
    $second = $this->service->invite();

    expect($first->token)->not->toBe($second->token);
});

test('submit fills the applicant data and moves the pre-registration to in review', function () {
    $preRegistration = $this->service->invite();

    $this->service->submit($preRegistration, [
        'name' => 'Maria Silva',
        'document' => '52998224725',
        'email' => 'maria@example.com',
        'whatsapp' => '5511999998888',
        'resident_count' => 2,
    ]);

    $preRegistration->refresh();

    expect($preRegistration->status)->toBe(PreRegistrationStatus::InReview)
        ->and($preRegistration->name)->toBe('Maria Silva')
        ->and($preRegistration->document)->toBe('52998224725')
        ->and($preRegistration->resident_count)->toBe(2)
        ->and($preRegistration->submitted_at)->not->toBeNull();
});

test('submit rejects a pre-registration that was already submitted', function () {
    $preRegistration = TenantPreRegistration::factory()->inReview()->create();

    $this->service->submit($preRegistration, [
        'name' => 'Outro Nome',
        'document' => '52998224725',
        'email' => 'outro@example.com',
        'whatsapp' => '5511999998888',
        'resident_count' => 1,
    ]);
})->throws(InvalidArgumentException::class);

test('submit rejects an expired pre-registration', function () {
    $preRegistration = TenantPreRegistration::factory()->expired()->create();

    $this->service->submit($preRegistration, [
        'name' => 'Maria Silva',
        'document' => '52998224725',
        'email' => 'maria@example.com',
        'whatsapp' => '5511999998888',
        'resident_count' => 2,
    ]);
})->throws(InvalidArgumentException::class);

test('approve creates the tenant and a login with the forced temporary password', function () {
    $admin = User::factory()->admin()->create();
    $preRegistration = TenantPreRegistration::factory()->inReview()->create([
        'name' => 'Maria Silva',
        'document' => '52998224725',
        'email' => 'maria@example.com',
        'whatsapp' => '5511999998888',
        'resident_count' => 2,
    ]);

    $tenant = $this->service->approve($preRegistration, $admin);

    expect($tenant->name)->toBe('Maria Silva')
        ->and($tenant->document)->toBe('52998224725')
        ->and($tenant->email)->toBe('maria@example.com')
        ->and($tenant->whatsapp)->toBe('5511999998888')
        ->and($tenant->resident_count)->toBe(2)
        ->and($tenant->status)->toBe(TenantStatus::Active)
        ->and($tenant->user_id)->not->toBeNull();

    $user = $tenant->user;
    expect($user->hasRole('tenant'))->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and(Hash::check(TenantPreRegistrationService::TEMPORARY_PASSWORD, $user->password))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue();

    $preRegistration->refresh();
    expect($preRegistration->status)->toBe(PreRegistrationStatus::Approved)
        ->and($preRegistration->reviewed_at)->not->toBeNull()
        ->and($preRegistration->reviewed_by)->toBe($admin->id)
        ->and($preRegistration->tenant_id)->toBe($tenant->id);
});

test('approve refuses a pre-registration that is not in review', function () {
    $admin = User::factory()->admin()->create();
    $preRegistration = $this->service->invite();

    $this->service->approve($preRegistration, $admin);
})->throws(InvalidArgumentException::class);

test('approve refuses a pre-registration that was already approved', function () {
    $admin = User::factory()->admin()->create();
    $preRegistration = TenantPreRegistration::factory()->inReview()->create();
    $this->service->approve($preRegistration, $admin);

    $this->service->approve($preRegistration->fresh(), $admin);
})->throws(InvalidArgumentException::class);

test('reject marks the pre-registration as rejected with a note', function () {
    $admin = User::factory()->admin()->create();
    $preRegistration = TenantPreRegistration::factory()->inReview()->create();

    $this->service->reject($preRegistration, $admin, 'Documento ilegível.');

    $preRegistration->refresh();
    expect($preRegistration->status)->toBe(PreRegistrationStatus::Rejected)
        ->and($preRegistration->reviewed_by)->toBe($admin->id)
        ->and($preRegistration->rejection_note)->toBe('Documento ilegível.')
        ->and(Tenant::query()->count())->toBe(0);
});

test('reject refuses a pre-registration that is not in review', function () {
    $admin = User::factory()->admin()->create();
    $preRegistration = $this->service->invite();

    $this->service->reject($preRegistration, $admin, null);
})->throws(InvalidArgumentException::class);
