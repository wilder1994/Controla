<?php

declare(strict_types=1);

use App\Http\Controllers\Platform\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'password.changed', 'active', 'platform.admin', 'tenant.unscoped'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:platform.dashboard')
            ->name('dashboard');
    });
