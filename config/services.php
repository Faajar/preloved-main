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

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

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

    'doku' => [
        'base_url' => env('DOKU_BASE_URL', 'https://api-sandbox.doku.com'),
        'client_id' => env('DOKU_CLIENT_ID'),
        'secret_key' => env('DOKU_SECRET_KEY'),
        'payment_due_minutes' => (int) env('DOKU_PAYMENT_DUE_MINUTES', 60),
        'webhook_tolerance_seconds' => (int) env('DOKU_WEBHOOK_TOLERANCE_SECONDS', 300),
        'connect_timeout_seconds' => (int) env('DOKU_CONNECT_TIMEOUT_SECONDS', 5),
        'timeout_seconds' => (int) env('DOKU_TIMEOUT_SECONDS', 15),
        'allowed_checkout_hosts' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('DOKU_ALLOWED_CHECKOUT_HOSTS', 'sandbox.doku.com')),
        ))),
    ],

];
