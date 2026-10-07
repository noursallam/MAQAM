<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    'gemini' => [
        // Supports both spellings; project .env currently uses GEMENI_API_KEY
        'key' => env('GEMINI_API_KEY', env('GEMENI_API_KEY')),
        'model' => env('GEMINI_MODEL', 'gemini-flash-lite-latest'),
        'cache_minutes' => (int) env('GEMINI_CACHE_MINUTES', 1440),
    ],

    'firebase' => [
        // Service-account key (Firebase console > Project settings > Service accounts). Never place it under public/
        'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/private/firebase/service-account.json')),
    ],

    'senderbot' => [
        // WhatsApp microservice (see docs/Senderbot.json); SENDER_BOT is the bearer token
        'url' => env('SENDER_BOT_URL', 'http://127.0.0.1:3333'),
        'token' => env('SENDER_BOT'),
        'session' => env('SENDER_BOT_SESSION', 'maqam'),
    ],

];
