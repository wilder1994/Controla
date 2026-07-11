<?php

declare(strict_types=1);

use App\Http\Controllers\Client\AppUserController;
use App\Http\Controllers\Client\AuthorizationController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\MemberController;
use App\Http\Controllers\Client\StructureController;
use App\Http\Controllers\Client\VehicleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'password.changed', 'active', 'tenancy.access', 'client.admin'])
    ->prefix('client')
    ->name('client.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:client.structures.manage')
            ->name('dashboard');

        Route::get('/structures', [StructureController::class, 'index'])
            ->middleware('permission:client.structures.manage')
            ->name('structures.index');
        Route::post('/structures', [StructureController::class, 'store'])
            ->middleware('permission:client.structures.manage')
            ->name('structures.store');
        Route::get('/structures/{structure}', [StructureController::class, 'show'])
            ->middleware('permission:client.structures.manage')
            ->name('structures.show');

        Route::get('/members', [MemberController::class, 'index'])
            ->middleware('permission:client.members.manage')
            ->name('members.index');
        Route::get('/members/create', [MemberController::class, 'create'])
            ->middleware('permission:client.members.manage')
            ->name('members.create');
        Route::post('/members/create', [MemberController::class, 'storeStep1'])
            ->middleware('permission:client.members.manage')
            ->name('members.create.step1');
        Route::get('/members/create/confirm', [MemberController::class, 'createConfirm'])
            ->middleware('permission:client.members.manage')
            ->name('members.create.confirm');
        Route::post('/members', [MemberController::class, 'store'])
            ->middleware('permission:client.members.manage')
            ->name('members.store');
        Route::get('/members/{member}', [MemberController::class, 'show'])
            ->middleware('permission:client.members.manage')
            ->name('members.show');
        Route::patch('/members/{member}', [MemberController::class, 'update'])
            ->middleware('permission:client.members.manage')
            ->name('members.update');

        Route::get('/vehicles', [VehicleController::class, 'index'])
            ->middleware('permission:client.vehicles.manage')
            ->name('vehicles.index');
        Route::get('/vehicles/create', [VehicleController::class, 'create'])
            ->middleware('permission:client.vehicles.manage')
            ->name('vehicles.create');
        Route::post('/vehicles', [VehicleController::class, 'store'])
            ->middleware('permission:client.vehicles.manage')
            ->name('vehicles.store');
        Route::get('/vehicles/{vehicle}/edit', [VehicleController::class, 'edit'])
            ->middleware('permission:client.vehicles.manage')
            ->name('vehicles.edit');
        Route::patch('/vehicles/{vehicle}', [VehicleController::class, 'update'])
            ->middleware('permission:client.vehicles.manage')
            ->name('vehicles.update');

        Route::get('/authorizations', [AuthorizationController::class, 'index'])
            ->middleware('permission:client.authorizations.manage')
            ->name('authorizations.index');
        Route::get('/authorizations/create', [AuthorizationController::class, 'create'])
            ->middleware('permission:client.authorizations.manage')
            ->name('authorizations.create');
        Route::post('/authorizations', [AuthorizationController::class, 'store'])
            ->middleware('permission:client.authorizations.manage')
            ->name('authorizations.store');
        Route::get('/authorizations/import', [AuthorizationController::class, 'importForm'])
            ->middleware('permission:client.authorizations.manage')
            ->name('authorizations.import');
        Route::post('/authorizations/import', [AuthorizationController::class, 'import'])
            ->middleware('permission:client.authorizations.manage')
            ->name('authorizations.import.store');

        Route::get('/app-users', [AppUserController::class, 'index'])
            ->middleware('permission:client.app_users.manage')
            ->name('app-users.index');
        Route::get('/app-users/create', [AppUserController::class, 'create'])
            ->middleware('permission:client.app_users.manage')
            ->name('app-users.create');
        Route::post('/app-users', [AppUserController::class, 'store'])
            ->middleware('permission:client.app_users.manage')
            ->name('app-users.store');
    });
