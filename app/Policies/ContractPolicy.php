<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(...UserRole::cases());
    }

    public function view(User $user, Contract $contract): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->hasRole(UserRole::Tenant)) {
            return $contract->tenant?->user_id === $user->id;
        }

        if ($user->hasRole(UserRole::Receiver)) {
            return $contract->receiver?->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
