<?php

declare(strict_types=1);

use App\Http\Controllers\Company\ClientController;
use App\Http\Controllers\Company\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'password.changed', 'active', 'company', 'tenant.unscoped'])
    ->prefix('company')
    ->name('company.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:company.dashboard')
            ->name('dashboard');

        Route::get('/clients/select', [ClientController::class, 'select'])
            ->name('clients.select');

        Route::post('/clients/{client}/activate', [ClientController::class, 'activate'])
            ->name('clients.activate');

        Route::post('/clients/{client}/assign', [ClientController::class, 'assign'])
            ->middleware('permission:company.users.assign')
            ->name('clients.assign');

        Route::delete('/clients/{client}/assign/{user}', [ClientController::class, 'unassign'])
            ->middleware('permission:company.users.assign')
            ->name('clients.unassign');

        Route::resource('clients', ClientController::class);
    });
