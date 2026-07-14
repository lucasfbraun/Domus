<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Expiring = 'expiring';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
