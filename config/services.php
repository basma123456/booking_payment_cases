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


    'myfatoorah' => [
        'api_key' => env('MYFATOORAH_API_KEY'),

        'webhook_secret' => env('MYFATOORAH_WEBHOOK_SECRET'),

        'test_mode' => env('MYFATOORAH_TEST_MODE', true),

        'test_url' => 'https://apitest.myfatoorah.com',

        'live_url' => 'https://api-eg.myfatoorah.com',

        'success_url' => env('MYFATOORAH_SUCCESS_URL'),

        'error_url' => env('MYFATOORAH_ERROR_URL'),

        'webhook_url' => env('MYFATOORAH_WEBHOOK_URL'),
    ],

];
