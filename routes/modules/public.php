<?php

declare(strict_types=1);

use App\Http\Controllers\Public\PlansController;
use App\Http\Controllers\Public\SignupCheckoutController;
use App\Http\Controllers\Public\SignupController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/planes', [PlansController::class, 'index'])
        ->name('planes.index');

    Route::get('/contratar', [SignupController::class, 'create'])
        ->name('signup.create');

    Route::get('/contratar/datos/{intent}', [SignupController::class, 'showData'])
        ->name('signup.data');

    Route::post('/contratar/datos/{intent}', [SignupController::class, 'storeData'])
        ->name('signup.data.store');

    Route::get('/contratar/legal/{intent}', [SignupController::class, 'showLegal'])
        ->name('signup.legal');

    Route::post('/contratar/legal/{intent}', [SignupController::class, 'storeLegal'])
        ->name('signup.legal.store');

    Route::get('/contratar/resumen/{intent}', [SignupController::class, 'showSummary'])
        ->name('signup.summary');

    Route::post('/contratar/pagar/{intent}', [SignupController::class, 'pay'])
        ->name('signup.pay');

    Route::get('/contratar/checkout/{intent}', [SignupCheckoutController::class, 'show'])
        ->name('signup.checkout.show');

    Route::post('/contratar/checkout/{intent}/approve', [SignupCheckoutController::class, 'approve'])
        ->name('signup.checkout.approve');

    Route::post('/contratar/checkout/{intent}/reject', [SignupCheckoutController::class, 'reject'])
        ->name('signup.checkout.reject');
});
