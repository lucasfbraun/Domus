<?php

namespace App\Services;

use App\Enums\PreRegistrationStatus;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\TenantPreRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Self-service intake for a future Tenant: an admin generates an invite
 * link (no data yet), the applicant fills it in themselves, and an admin
 * reviews before anything real is created — see
 * docs/adr/0009-tenant-pre-registration.md.
 */
class TenantPreRegistrationService
{
    /**
     * Fixed temporary password set on the User created at approval. The
     * admin hands this to the tenant directly; {@see User::$must_change_password}
     * forces them to replace it before they can do anything else.
     */
    public const TEMPORARY_PASSWORD = 'Muda@123';

    private const EXPIRES_AFTER_DAYS = 7;

    public function __construct(private PortalAccountService $portalAccounts) {}

    public function invite(): TenantPreRegistration
    {
        return TenantPreRegistration::query()->create([
            'token' => Str::random(40),
            'status' => PreRegistrationStatus::Pending,
            'invited_at' => now(),
            'expires_at' => now()->addDays(self::EXPIRES_AFTER_DAYS),
        ]);
    }

    /**
     * @param  array{name: string, document: string, email: string, whatsapp: string, resident_count: int}  $data
     */
    public function submit(TenantPreRegistration $preRegistration, array $data): void
    {
        if ($preRegistration->status !== PreRegistrationStatus::Pending) {
            throw new InvalidArgumentException('Este link de pre-cadastro ja foi preenchido.');
        }

        if ($preRegistration->isExpired()) {
            throw new InvalidArgumentException('Este link de pre-cadastro expirou.');
        }

        $preRegistration->update([
            ...$data,
            'status' => PreRegistrationStatus::InReview,
            'submitted_at' => now(),
        ]);
    }

    public function approve(TenantPreRegistration $preRegistration, User $reviewer): Tenant
    {
        $this->assertInReview($preRegistration);

        return DB::transaction(function () use ($preRegistration, $reviewer) {
            $userId = $this->portalAccounts->sync(
                role: UserRole::Tenant,
                currentUserId: null,
                existingUserId: null,
                name: $preRegistration->name,
                email: $preRegistration->email,
                password: self::TEMPORARY_PASSWORD,
            );

            $this->portalAccounts->forcePasswordChangeOnNextLogin($userId);

            $tenant = Tenant::query()->create([
                'user_id' => $userId,
                'name' => $preRegistration->name,
                'document' => $preRegistration->document,
                'email' => $preRegistration->email,
                'whatsapp' => $preRegistration->whatsapp,
                'resident_count' => $preRegistration->resident_count,
                'status' => TenantStatus::Active,
            ]);

            $preRegistration->update([
                'status' => PreRegistrationStatus::Approved,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'tenant_id' => $tenant->id,
            ]);

            return $tenant;
        });
    }

    public function reject(TenantPreRegistration $preRegistration, User $reviewer, ?string $note): void
    {
        $this->assertInReview($preRegistration);

        $preRegistration->update([
            'status' => PreRegistrationStatus::Rejected,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer->id,
            'rejection_note' => $note,
        ]);
    }

    private function assertInReview(TenantPreRegistration $preRegistration): void
    {
        if ($preRegistration->status !== PreRegistrationStatus::InReview) {
            throw new InvalidArgumentException('Este pre-cadastro nao esta aguardando analise.');
        }
    }
}
