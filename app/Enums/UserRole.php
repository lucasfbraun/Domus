<?php

namespace App\Enums;

/**
 * Authentication role of a User. Tenant/Receiver/Owner mirror the domain
 * records they can be linked to (via `user_id`) — and, since a login can now
 * be shared, a single User may hold more than one of these roles at once
 * (see docs/adr/0006-shared-login-across-roles.md and User::homeRouteName()
 * for how the "home" route is picked when that happens).
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Tenant = 'tenant';
    case Receiver = 'receiver';
    case Owner = 'owner';

    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'dashboard',
            self::Tenant => 'tenant.portal',
            self::Receiver => 'receiver.portal',
            self::Owner => 'owner.portal',
        };
    }
}
