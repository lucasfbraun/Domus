<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Charge;
use App\Models\User;

class ChargePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(...UserRole::cases());
    }

    public function view(User $user, Charge $charge): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->hasRole(UserRole::Tenant)) {
            return $charge->contract?->tenant?->user_id === $user->id;
        }

        if ($user->hasRole(UserRole::Receiver)) {
            return $charge->receiver?->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function update(User $user, Charge $charge): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->hasRole(UserRole::Tenant)) {
            return $charge->contract?->tenant?->user_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Charge $charge): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
