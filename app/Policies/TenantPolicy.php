<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;

/**
 * Managing Tenant records (cadastro) is admin-only. A tenant's own portal
 * access is governed by ChargePolicy/ContractPolicy/DepositPolicy, not by
 * this policy.
 */
class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
