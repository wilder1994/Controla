<?php

declare(strict_types=1);

use App\Http\Controllers\Billing\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'password.changed', 'active'])
    ->prefix('billing')
    ->name('billing.')
    ->group(function () {
        Route::get('/checkout/{payment}', [CheckoutController::class, 'show'])
            ->name('checkout.show');

        Route::post('/checkout/{payment}/approve', [CheckoutController::class, 'approve'])
            ->name('checkout.approve');

        Route::post('/checkout/{payment}/reject', [CheckoutController::class, 'reject'])
            ->name('checkout.reject');
    });
