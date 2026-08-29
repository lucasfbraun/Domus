<?php

namespace App\Enums;

/**
 * Standing of a Tenant, set by the admin. Delinquent flags a tenant with
 * payment problems; it doesn't get set automatically from overdue Charges.
 */
enum TenantStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Delinquent = 'delinquent';
    case Former = 'former';
}
