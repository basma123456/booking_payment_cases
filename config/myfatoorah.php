<?php

return [
    'base_url' => env('MYFATOORAH_BASE_URL', 'https://api-eg.myfatoorah.com'),
    'api_key' => env('MYFATOORAH_API_KEY'),
    'webhook_secret' => env('MYFATOORAH_WEBHOOK_SECRET'),
    'currency' => env('MYFATOORAH_CURRENCY', 'EGP'),
    'default_payment_method' => env('MYFATOORAH_PAYMENT_METHOD', 'CARD'),
    'timeout' => (int) env('MYFATOORAH_TIMEOUT', 30),
];
