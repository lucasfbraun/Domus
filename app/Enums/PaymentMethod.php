<?php

namespace App\Enums;

/**
 * How a Payment was collected. Pix and CreditCard are inferred from the
 * Mercado Pago payment method reported on the webhook (MercadoPagoService::
 * mapPaymentMethod()); the app itself only ever generates Pix orders.
 * Manual is declared for future use and isn't produced anywhere yet.
 */
enum PaymentMethod: string
{
    case Pix = 'pix';
    case CreditCard = 'credit_card';
    case Manual = 'manual';
}
