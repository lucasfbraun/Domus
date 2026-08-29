<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Receiver;
use App\Models\User;

/**
 * Managing Receiver records (cadastro, Mercado Pago connection) is
 * admin-only. A Receiver's own portal access is governed by ChargePolicy/
 * ContractPolicy/DepositPolicy, not by this policy.
 */
class ReceiverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function view(User $user, Receiver $receiver): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function update(User $user, Receiver $receiver): bool
    {
        return $user->hasRole(UserRole::Admin);
    }

    public function delete(User $user, Receiver $receiver): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}
