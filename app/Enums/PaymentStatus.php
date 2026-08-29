<?php

namespace App\Enums;

/**
 * Status of a single Payment record, mirroring the Mercado Pago order/payment
 * status reported via webhook or manual sync.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
