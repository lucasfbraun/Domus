<?php

namespace App\Enums;

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
