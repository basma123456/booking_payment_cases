<?php

use App\Http\Controllers\Admin\PurchaseController as AdminPurchaseController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PurchaseController;
use Illuminate\Support\Facades\Route;

//Route::middleware('auth:sanctum')->group(function () {
    Route::post('/purchases', [PurchaseController::class, 'store']);
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show']);
    Route::post('/purchases/{purchase}/payment', [PaymentController::class, 'store']);
//});


//Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
Route::prefix('admin')->group(function () {
    Route::get('/purchases', [AdminPurchaseController::class, 'index']);
    Route::get('/purchases/{purchase}', [AdminPurchaseController::class, 'show']);
});
