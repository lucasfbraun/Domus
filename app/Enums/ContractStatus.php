<?php

namespace App\Enums;

/**
 * Lease term lifecycle of a Contract, independent of SignatureStatus —
 * a contract can be Active with no signed document at all.
 */
enum ContractStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Expiring = 'expiring';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
