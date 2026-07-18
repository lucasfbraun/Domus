<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Deposit;
use App\Models\User;

class DepositPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(...UserRole::cases());
    }

    public function view(User $user, Deposit $deposit): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->hasRole(UserRole::Tenant)) {
            return $deposit->contract?->tenant?->user_id === $user->id;
        }

        if ($user->hasRole(UserRole::Receiver)) {
            return $deposit->receiver?->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function update(User $user, Deposit $deposit): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->hasRole(UserRole::Tenant)) {
            return $deposit->contract?->tenant?->user_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Deposit $deposit): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
