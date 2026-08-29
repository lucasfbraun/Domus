<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Rateio;
use App\Models\User;

/**
 * Rateio records are admin-only; tenants/receivers never see the split
 * itself, only the Charge it produces on their own contract.
 */
class RateioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function view(User $user, Rateio $rateio): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function update(User $user, Rateio $rateio): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function delete(User $user, Rateio $rateio): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
