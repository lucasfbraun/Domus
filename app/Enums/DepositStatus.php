<?php

namespace App\Enums;

/**
 * Lifecycle of a security deposit (Caução), separate from Charge/ChargeStatus
 * since it's collected outside the monthly rent cycle.
 */
enum DepositStatus: string
{
    case Pending = 'pending';
    case WaitingPayment = 'waiting_payment';
    case Paid = 'paid';
    case Refunded = 'refunded';
}
