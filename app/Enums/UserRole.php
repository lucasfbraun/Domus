<?php

namespace App\Enums;

/**
 * Authentication role of a User. Tenant/Receiver mirror the Tenant/Receiver
 * domain records they can be linked to (via `user_id`); Owner never gets a
 * User or a role — it has no login of its own.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Tenant = 'tenant';
    case Receiver = 'receiver';

    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'dashboard',
            self::Tenant => 'tenant.portal',
            self::Receiver => 'receiver.portal',
        };
    }
}
