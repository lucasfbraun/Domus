<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\User;

/** Property records are admin-only; no tenant/receiver-scoped access. */
class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function view(User $user, Property $property): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function update(User $user, Property $property): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
