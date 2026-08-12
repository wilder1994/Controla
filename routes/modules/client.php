<?php

declare(strict_types=1);

use App\Http\Controllers\Client\AppUserController;
use App\Http\Controllers\Client\AuthorizationController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\MemberController;
use App\Http\Controllers\Client\PetController;
use App\Http\Controllers\Client\StructureController;
use App\Http\Controllers\Client\UserController;
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
        Route::post('/members', [MemberController::class, 'store'])
            ->middleware('permission:client.members.manage')
            ->name('members.store');
        Route::get('/members/{member}', [MemberController::class, 'show'])
            ->middleware('permission:client.members.manage')
            ->name('members.show');
        Route::get('/members/{member}/edit', [MemberController::class, 'edit'])
            ->middleware('permission:client.members.manage')
            ->name('members.edit');
        Route::put('/members/{member}', [MemberController::class, 'update'])
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

        Route::get('/pets', [PetController::class, 'index'])
            ->middleware('permission:client.pets.manage')
            ->name('pets.index');
        Route::get('/pets/create', [PetController::class, 'create'])
            ->middleware('permission:client.pets.manage')
            ->name('pets.create');
        Route::post('/pets', [PetController::class, 'store'])
            ->middleware('permission:client.pets.manage')
            ->name('pets.store');
        Route::get('/pets/{pet}', [PetController::class, 'show'])
            ->middleware('permission:client.pets.manage')
            ->name('pets.show');

        Route::get('/members/export', [MemberController::class, 'export'])
            ->middleware('permission:client.members.manage')
            ->name('members.export');

        Route::get('/app-users', [AppUserController::class, 'index'])
            ->middleware('permission:client.app_users.manage')
            ->name('app-users.index');
        Route::get('/app-users/create', [AppUserController::class, 'create'])
            ->middleware('permission:client.app_users.manage')
            ->name('app-users.create');
        Route::post('/app-users', [AppUserController::class, 'store'])
            ->middleware('permission:client.app_users.manage')
            ->name('app-users.store');

        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:client.users.manage')
            ->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])
            ->middleware('permission:client.users.manage')
            ->name('users.create');
        Route::post('/users', [UserController::class, 'store'])
            ->middleware('permission:client.users.manage')
            ->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])
            ->middleware('permission:client.users.manage')
            ->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])
            ->middleware('permission:client.users.manage')
            ->name('users.update');
    });
