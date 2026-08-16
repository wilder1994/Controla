<?php

declare(strict_types=1);

use App\Http\Controllers\Company\BillingCheckoutController;
use App\Http\Controllers\Company\BillingController;
use App\Http\Controllers\Company\ClientController;
use App\Http\Controllers\Company\DashboardController;
use App\Http\Controllers\Company\PorteriaController;
use App\Http\Controllers\Company\SettingsController;
use App\Http\Controllers\Company\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'password.changed', 'active', 'company', 'tenant.unscoped'])
    ->prefix('company')
    ->name('company.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:company.dashboard')
            ->name('dashboard');

        Route::get('/billing', [BillingController::class, 'index'])
            ->middleware('permission:company.dashboard')
            ->name('billing.index');

        Route::post('/billing/checkout', [BillingCheckoutController::class, 'store'])
            ->middleware('permission:company.dashboard')
            ->name('billing.checkout');

        Route::post('/billing/membership/cancel', [BillingController::class, 'cancelMembership'])
            ->middleware('permission:company.dashboard')
            ->name('billing.membership.cancel');

        Route::post('/billing/membership/undo-cancel', [BillingController::class, 'undoCancellation'])
            ->middleware('permission:company.dashboard')
            ->name('billing.membership.undo-cancel');

        Route::post('/billing/package/schedule', [BillingController::class, 'schedulePackageChange'])
            ->middleware('permission:company.dashboard')
            ->name('billing.package.schedule');

        Route::get('/settings', [SettingsController::class, 'edit'])
            ->middleware('permission:company.settings.manage')
            ->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])
            ->middleware('permission:company.settings.manage')
            ->name('settings.update');

        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:company.users.assign')
            ->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])
            ->middleware('permission:company.users.assign')
            ->name('users.create');
        Route::post('/users', [UserController::class, 'store'])
            ->middleware('permission:company.users.assign')
            ->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])
            ->middleware('permission:company.users.assign')
            ->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])
            ->middleware('permission:company.users.assign')
            ->name('users.update');

        Route::get('/porteria', [PorteriaController::class, 'enter'])
            ->name('porteria.enter');

        Route::redirect('/clients/select', '/company/porteria')
            ->name('clients.select');

        Route::post('/clients/{client}/activate', [ClientController::class, 'activate'])
            ->name('clients.activate');

        Route::post('/clients/{client}/operate-client', [ClientController::class, 'operateClient'])
            ->name('clients.operate-client');

        Route::post('/operate/exit', [ClientController::class, 'exitOperate'])
            ->name('operate.exit');

        Route::resource('clients', ClientController::class);
    });
