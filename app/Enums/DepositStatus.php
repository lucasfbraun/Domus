<?php

namespace App\Enums;

enum DepositStatus: string
{
    case Pending = 'pending';
    case WaitingPayment = 'waiting_payment';
    case Paid = 'paid';
    case Refunded = 'refunded';
}
