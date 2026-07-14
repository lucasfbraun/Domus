<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | ISO 4217 currency code used across the application. All monetary columns
    | in the database store values in this currency's major unit (e.g. reais).
    |
    */

    'currency' => env('MONEY_CURRENCY', 'BRL'),

    /*
    |--------------------------------------------------------------------------
    | Display Locale
    |--------------------------------------------------------------------------
    |
    | Locale passed to Intl.NumberFormat on the frontend and used for human-
    | readable formatting on the backend.
    |
    */

    'locale' => env('MONEY_LOCALE', 'pt-BR'),

    /*
    |--------------------------------------------------------------------------
    | Decimal Places
    |--------------------------------------------------------------------------
    |
    | Number of fractional digits for monetary amounts. Database columns and
    | Eloquent decimal casts should match this value.
    |
    */

    'decimals' => (int) env('MONEY_DECIMALS', 2),

    /*
    |--------------------------------------------------------------------------
    | Currency Symbol
    |--------------------------------------------------------------------------
    |
    | Prefix used in plain-text contexts (notifications, PDFs, WhatsApp).
    | Intl formatting on the frontend derives the symbol from the locale.
    |
    */

    'symbol' => env('MONEY_SYMBOL', 'R$'),

];
