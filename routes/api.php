<?php

use App\Http\Controllers\Api\BankNotificationController;
use App\Http\Controllers\Api\CompanionAuthController;
use App\Http\Controllers\Api\CompanionContactsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('companion')->group(function () {
    Route::post('/login', [CompanionAuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::post('/logout', [CompanionAuthController::class, 'logout']);
        Route::post('/contacts/sync', [CompanionContactsController::class, 'sync']);
        Route::post('/bank-sms', [BankNotificationController::class, 'handleSms']);
        Route::post('/bank-payment', [BankNotificationController::class, 'handleIncomingPayment']);
    });
});
