<?php

declare(strict_types=1);

use App\Http\Controllers\Resident\CorrespondenceController;
use App\Http\Controllers\Resident\DashboardController;
use App\Http\Controllers\Resident\MessageController;
use App\Http\Controllers\Resident\NotificationController;
use App\Http\Controllers\Resident\PreAuthorizationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'password.changed', 'active', 'tenancy.access'])
    ->prefix('resident')
    ->name('resident.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:resident.portal.access')
            ->name('dashboard');

        Route::get('/pre-authorizations', [PreAuthorizationController::class, 'index'])
            ->middleware('permission:access.manage.pre_authorizations')
            ->name('pre-authorizations.index');
        Route::get('/pre-authorizations/create', [PreAuthorizationController::class, 'create'])
            ->middleware('permission:access.manage.pre_authorizations')
            ->name('pre-authorizations.create');
        Route::post('/pre-authorizations', [PreAuthorizationController::class, 'store'])
            ->middleware('permission:access.manage.pre_authorizations')
            ->name('pre-authorizations.store');
        Route::delete('/pre-authorizations/{preAuthorization}', [PreAuthorizationController::class, 'destroy'])
            ->middleware('permission:access.manage.pre_authorizations')
            ->name('pre-authorizations.destroy');

        Route::get('/correspondence', [CorrespondenceController::class, 'index'])
            ->name('correspondence.index');
        Route::get('/correspondence/{correspondence}', [CorrespondenceController::class, 'show'])
            ->name('correspondence.show');

        Route::get('/messages', [MessageController::class, 'inbox'])
            ->name('messages.inbox');
        Route::get('/messages/sent', [MessageController::class, 'sent'])
            ->name('messages.sent');
        Route::get('/messages/create', [MessageController::class, 'create'])
            ->name('messages.create');
        Route::post('/messages', [MessageController::class, 'store'])
            ->name('messages.store');
        Route::get('/messages/{message}', [MessageController::class, 'show'])
            ->name('messages.show');

        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
            ->name('notifications.read-all');
    });
