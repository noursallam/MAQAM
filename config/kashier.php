<?php

return [
    'mode' => env('KASHIER_MODE', 'test'),
    'mid' => env('KASHIER_MID', 'MID-48774-759'),
    'payment_api_key' => env('KASHIER_PAYMENT_API_KEY'),
    'secret_key' => env('KASHIER_SECRET_KEY'),
    'currency' => env('KASHIER_CURRENCY', 'EGP'),

    'api_url' => env('KASHIER_MODE', 'test') === 'live'
        ? 'https://api.kashier.io'
        : 'https://test-api.kashier.io',

    'fep_url' => env('KASHIER_MODE', 'test') === 'live'
        ? 'https://fep.kashier.io'
        : 'https://test-fep.kashier.io',

    'checkout_url' => 'https://payments.kashier.io',

    'merchant_redirect' => env('KASHIER_MERCHANT_REDIRECT'),
    'server_webhook' => env('KASHIER_SERVER_WEBHOOK'),

    'ssl_verify' => env('KASHIER_SSL_VERIFY', false),
];
