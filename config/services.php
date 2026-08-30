<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'mercadopago' => [
        'client_id' => env('MP_CLIENT_ID'),
        'client_secret' => env('MP_CLIENT_SECRET'),
        'access_token' => env('MP_ACCESS_TOKEN'),
        'public_key' => env('MP_PUBLIC_KEY'),
        'webhook_secret' => env('MP_WEBHOOK_SECRET'),
        'sandbox_connect' => (bool) env('MP_SANDBOX_CONNECT', false),
    ],

    'waha' => [
        'url' => env('WAHA_BASE_URL'),
        'api_key' => env('WAHA_API_KEY'),
        'session' => env('WAHA_SESSION', 'default'),
    ],

    'app_base_url' => env('APP_BASE_URL', env('APP_URL')),

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'feature_checks' => [
        // Running the real test suite from the admin UI only makes sense in
        // local dev: production ships without dev dependencies (see
        // Dockerfile's `composer install --no-dev`) and a full run takes
        // several minutes. Override with FEATURE_CHECKS_ENABLED for a
        // non-local sandbox where dev deps happen to be installed; tests
        // override this directly via config(), not this env default.
        'enabled' => (bool) env('FEATURE_CHECKS_ENABLED', env('APP_ENV') === 'local'),
    ],

];
