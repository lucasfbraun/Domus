<?php

namespace App\Enums;

/**
 * Lifecycle of a single monthly rent installment (Charge).
 *
 * Open -> WaitingPayment (Pix generated) -> Paid, or Open -> Overdue once
 * the due date passes unpaid. Cancelled is terminal and skips the rest.
 */
enum ChargeStatus: string
{
    case Open = 'open';
    case WaitingPayment = 'waiting_payment';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
}
