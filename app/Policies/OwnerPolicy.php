<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Owner;
use App\Models\User;

/**
 * Managing Owner records (cadastro, linking/creating their portal login) is
 * admin-only. An Owner's own portal access is governed separately — see
 * OwnerPortalController — not by this policy.
 */
class OwnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function view(User $user, Owner $owner): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function update(User $user, Owner $owner): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function delete(User $user, Owner $owner): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
