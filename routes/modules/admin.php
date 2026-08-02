<?php

declare(strict_types=1);

use App\Http\Controllers\Platform\CompanyController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\DocumentController;
use App\Http\Controllers\Platform\PricingController;
use App\Http\Controllers\Platform\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'password.changed', 'active', 'platform.admin', 'tenant.unscoped'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:platform.dashboard')
            ->name('dashboard');

        Route::post('/companies/{company}/archive', [DashboardController::class, 'archiveCompany'])
            ->middleware('permission:platform.companies.manage')
            ->name('companies.archive');

        Route::post('/companies/{company}/clients/{client}/release', [DashboardController::class, 'releaseClient'])
            ->middleware('permission:platform.companies.manage')
            ->name('companies.clients.release');

        Route::get('/pricing', [PricingController::class, 'edit'])
            ->middleware('permission:platform.companies.view')
            ->name('pricing.edit');

        Route::put('/pricing', [PricingController::class, 'update'])
            ->middleware('permission:platform.companies.manage')
            ->name('pricing.update');

        Route::get('/companies', [CompanyController::class, 'index'])
            ->middleware('permission:platform.companies.view')
            ->name('companies.index');

        Route::get('/companies/{company}', [CompanyController::class, 'show'])
            ->middleware('permission:platform.companies.view')
            ->name('companies.show');

        Route::put('/companies/{company}/package', [CompanyController::class, 'updatePackage'])
            ->middleware('permission:platform.companies.manage')
            ->name('companies.package.update');

        Route::get('/companies/{company}/profile', [CompanyController::class, 'editProfile'])
            ->middleware('permission:platform.companies.manage')
            ->name('companies.profile.edit');

        Route::put('/companies/{company}/profile', [CompanyController::class, 'updateProfile'])
            ->middleware('permission:platform.companies.manage')
            ->name('companies.profile.update');

        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:platform.users.view')
            ->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])
            ->middleware('permission:platform.users.manage')
            ->name('users.create');
        Route::post('/users', [UserController::class, 'store'])
            ->middleware('permission:platform.users.manage')
            ->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])
            ->middleware('permission:platform.users.view')
            ->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])
            ->middleware('permission:platform.users.manage')
            ->name('users.update');

        Route::get('/documents', [DocumentController::class, 'index'])
            ->middleware('permission:platform.documents.view')
            ->name('documents.index');

        Route::get('/documents/normativa', [DocumentController::class, 'normativa'])
            ->middleware('permission:platform.documents.view')
            ->name('documents.normativa');

        Route::get('/documents/trd', [DocumentController::class, 'trd'])
            ->middleware('permission:platform.documents.view')
            ->name('documents.trd');

        Route::get('/documents/expedientes', [DocumentController::class, 'expedientes'])
            ->middleware('permission:platform.documents.view')
            ->name('documents.expedientes');

        Route::get('/documents/expedientes/{company}', [DocumentController::class, 'showExpediente'])
            ->middleware('permission:platform.documents.view')
            ->name('documents.expedientes.show');

        Route::post('/documents/expedientes/{company}/acceptance', [DocumentController::class, 'storeAcceptance'])
            ->middleware('permission:platform.documents.manage')
            ->name('documents.expedientes.acceptance');

        Route::post('/documents/expedientes/{company}/payments/manual', [DocumentController::class, 'storeManualPayment'])
            ->middleware('permission:platform.documents.manage')
            ->name('documents.expedientes.payment.manual');

        Route::post('/documents/expedientes/{company}/payments/local-checkout', [DocumentController::class, 'storeLocalCheckout'])
            ->middleware('permission:platform.documents.manage')
            ->name('documents.expedientes.payment.local-checkout');
    });
