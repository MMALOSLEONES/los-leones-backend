<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return view('welcome');
});
// Route::get('/payments/return', [PaymentController::class, 'handleReturn']);
// Route::get('/payments/cancel', [PaymentController::class, 'handleCancel']);