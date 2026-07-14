<?php

namespace App\Enums;

enum ChargeStatus: string
{
    case Open = 'open';
    case WaitingPayment = 'waiting_payment';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
}
