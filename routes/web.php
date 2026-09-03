<?php

use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Webhooks\MyFatoorahWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/payment/callback', [PaymentController::class, 'callback'])
    ->name('payments.callback');

Route::post('/webhooks/myfatoorah', MyFatoorahWebhookController::class)
    ->name('webhooks.myfatoorah');
