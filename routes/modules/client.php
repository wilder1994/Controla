<?php

declare(strict_types=1);

use App\Http\Controllers\Client\AppUserController;
use App\Http\Controllers\Client\AuthorizationController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\MemberController;
use App\Http\Controllers\Client\MemberTypeController;
use App\Http\Controllers\Client\PersonnelDocumentController;
use App\Http\Controllers\Client\PetController;
use App\Http\Controllers\Client\StructureController;
use App\Http\Controllers\Client\UserController;
use App\Http\Controllers\Client\VehicleController;
use App\Http\Controllers\Client\ZoneBookingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'password.changed', 'active', 'tenancy.access', 'client.admin'])
    ->prefix('client')
    ->name('client.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:client.structures.manage')
            ->name('dashboard');

        Route::middleware('permission:client.structures.manage')->group(function () {
            Route::get('/documents', [PersonnelDocumentController::class, 'index'])->name('personnel-documents.index');
            Route::get('/documents/file/{document}/preview', [PersonnelDocumentController::class, 'preview'])->name('personnel-documents.preview');
            Route::get('/documents/file/{document}/download', [PersonnelDocumentController::class, 'download'])->name('personnel-documents.download');
            Route::get('/documents/{employee}', [PersonnelDocumentController::class, 'folder'])->name('personnel-documents.folder');
        });

        Route::get('/structures', [StructureController::class, 'index'])
            ->middleware('permission:client.structures.manage')
            ->name('structures.index');
        Route::post('/structures', [StructureController::class, 'store'])
            ->middleware('permission:client.structures.manage')
            ->name('structures.store');
        Route::get('/structures/{structure}', [StructureController::class, 'show'])
            ->middleware('permission:client.structures.manage')
            ->name('structures.show');

        Route::get('/members/export', [MemberController::class, 'export'])
            ->middleware('permission:client.members.manage')
            ->name('members.export');

        Route::middleware('permission:client.members.manage')->prefix('settings')->name('settings.')->group(function () {
            Route::get('/member-types', [MemberTypeController::class, 'index'])->name('member-types.index');
        });
        Route::middleware('permission:client.settings.manage')->prefix('settings')->name('settings.')->group(function () {
            Route::post('/member-types', [MemberTypeController::class, 'store'])->name('member-types.store');
            Route::put('/member-types/{memberType}', [MemberTypeController::class, 'update'])->name('member-types.update');
            Route::delete('/member-types/{memberType}', [MemberTypeController::class, 'destroy'])->name('member-types.destroy');
        });

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

        Route::middleware(['permission:client.vehicles.manage', 'client.module:vehicles'])->group(function () {
            Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
            Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
            Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
        });

        Route::middleware(['permission:client.authorizations.manage', 'client.module:authorizations'])->group(function () {
            Route::get('/authorizations', [AuthorizationController::class, 'index'])->name('authorizations.index');
            Route::get('/authorizations/create', [AuthorizationController::class, 'create'])->name('authorizations.create');
            Route::post('/authorizations', [AuthorizationController::class, 'store'])->name('authorizations.store');
            Route::get('/authorizations/import', [AuthorizationController::class, 'importForm'])->name('authorizations.import');
            Route::post('/authorizations/import', [AuthorizationController::class, 'import'])->name('authorizations.import.store');
        });

        Route::middleware(['permission:client.pets.manage', 'client.module:pets'])->group(function () {
            Route::get('/pets', [PetController::class, 'index'])->name('pets.index');
            Route::get('/pets/create', [PetController::class, 'create'])->name('pets.create');
            Route::post('/pets', [PetController::class, 'store'])->name('pets.store');
            Route::get('/pets/{pet}', [PetController::class, 'show'])->name('pets.show');
        });

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

        // Zonas comunes
        Route::middleware('permission:client.zones.book')->group(function () {
            Route::get('/zones', [ZoneBookingController::class, 'index'])->name('zones.index');
            Route::get('/zones/book', [ZoneBookingController::class, 'create'])->name('zones.book');
            Route::post('/zones', [ZoneBookingController::class, 'store'])->name('zones.store');
            Route::post('/zones/{booking}/cancel', [ZoneBookingController::class, 'cancel'])->name('zones.cancel');
        });
    });
