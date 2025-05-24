<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Peach Payments Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the environment for the Peach Payments API.
    | Set to 'production' for live transactions or 'test' for testing.
    |
    */
    'environment' => env('PEACH_ENVIRONMENT', 'test'),

    /*
    |--------------------------------------------------------------------------
    | Authentication Credentials
    |--------------------------------------------------------------------------
    |
    | These are your Peach Payments API credentials.
    | Never share these credentials or commit them to version control.
    |
    */
    'client_id' => env('PEACH_CLIENT_ID'),
    'client_secret' => env('PEACH_CLIENT_SECRET'),
    'webhook_secret' => env('PEACH_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency to use for payments.
    |
    */
    'default_currency' => 'ZAR',

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Configure the webhook URL and events to listen for.
    |
    */
    'webhook' => [
        'path' => 'api/webhooks/peachpayments',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Settings
    |--------------------------------------------------------------------------
    |
    | Default subscription settings that can be overridden per subscription.
    |
    */
    'subscription' => [
        'trial_days' => 0,
        'grace_days' => 7,
        'payment_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for the package.
    |
    */
    'logging' => [
        'enabled' => true,
        'level' => 'debug',
    ],
];
