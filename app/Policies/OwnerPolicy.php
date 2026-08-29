<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Owner;
use App\Models\User;

/** Owner records are admin-only; owners never have their own User/login. */
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
