<?php

use App\Http\Controllers\Access\AccessLogController;
use App\Http\Controllers\Access\BuildingController;
use App\Http\Controllers\Access\CorrespondenceController;
use App\Http\Controllers\Access\DashboardController;
use App\Http\Controllers\Access\GuardLogController;
use App\Http\Controllers\Access\HousingUnitController;
use App\Http\Controllers\Access\LocationController;
use App\Http\Controllers\Access\PreAuthorizationController;
use App\Http\Controllers\Access\ReportController;
use App\Http\Controllers\Access\ResidentController;
use App\Http\Controllers\Access\VehicleAccessController;
use App\Http\Controllers\Access\VehicleController;
use App\Http\Controllers\Access\VisitorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'password.changed', 'active', 'tenancy.access'])
    ->prefix('access')
    ->name('access.')
    ->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:access.dashboard')
            ->name('dashboard');

        Route::middleware('permission:access.manage.locations')->group(function (): void {
            Route::resource('locations', LocationController::class)->except(['show']);
        });

        Route::middleware('permission:access.manage.buildings')->group(function (): void {
            Route::resource('buildings', BuildingController::class)->except(['show']);
        });

        Route::middleware('permission:access.manage.housing_units')->group(function (): void {
            Route::resource('housing_units', HousingUnitController::class)->except(['show']);
            Route::get('housing_units/by-building/{building}', [HousingUnitController::class, 'searchByBuildingJson'])
                ->name('housing_units.by_building');
        });

        Route::middleware('permission:access.manage.visitors')->group(function (): void {
            Route::resource('visitors', VisitorController::class);
            Route::get('visitors/search/json', [VisitorController::class, 'searchJson'])
                ->name('visitors.search.json');
        });

        Route::middleware('permission:access.manage.residents')->group(function (): void {
            Route::resource('residents', ResidentController::class);
            Route::get('residents/search/json', [ResidentController::class, 'searchJson'])
                ->name('residents.search.json');
            Route::get('residents/housing-units/json', [ResidentController::class, 'searchHousingUnitsJson'])
                ->name('residents.housing_units.json');
            Route::post('residents/{resident}/vehicles', [ResidentController::class, 'addVehicle'])
                ->name('residents.vehicles.store');
            Route::delete('residents/{resident}/vehicles/{vehicle}', [ResidentController::class, 'removeVehicle'])
                ->name('residents.vehicles.destroy');
        });

        Route::middleware('permission:access.manage.vehicles')->group(function (): void {
            Route::resource('vehicles', VehicleController::class)->except(['show']);
            Route::get('vehicles/search/json', [VehicleController::class, 'searchJson'])
                ->name('vehicles.search.json');
            Route::get('vehicles/search/resident/json', [VehicleController::class, 'searchResidentVehicleJson'])
                ->name('vehicles.search.resident.json');
        });

        Route::middleware('permission:access.manage.vehicle_access')->group(function (): void {
            Route::get('/vehicle-access', [VehicleAccessController::class, 'index'])->name('vehicle_access.index');
            Route::get('/vehicle-access/entry', [VehicleAccessController::class, 'entry'])->name('vehicle_access.entry');
            Route::post('/vehicle-access/entry', [VehicleAccessController::class, 'storeEntry'])->name('vehicle_access.entry.store');
            Route::patch('/vehicle-access/{accessLog}/exit', [VehicleAccessController::class, 'markExit'])->name('vehicle_access.exit');
            Route::get('/vehicle-access/search', [VehicleAccessController::class, 'searchVehicleJson'])->name('vehicle_access.search');
        });

        Route::middleware('permission:access.register.entry')->group(function (): void {
            Route::get('/logs', [AccessLogController::class, 'index'])->name('logs.index');
            Route::get('/logs/entry', [AccessLogController::class, 'entry'])->name('logs.entry');
            Route::post('/logs/entry', [AccessLogController::class, 'storeEntry'])->name('logs.entry.store');
        });

        Route::patch('/logs/{accessLog}/exit', [AccessLogController::class, 'markExit'])
            ->middleware('permission:access.register.exit')
            ->name('logs.exit');

        Route::middleware('permission:access.manage.pre_authorizations')->group(function (): void {
            Route::resource('pre_authorizations', PreAuthorizationController::class)->except(['edit', 'update']);
            Route::get('pre_authorizations/{preAuthorization}/qr', [PreAuthorizationController::class, 'qr'])
                ->name('pre_authorizations.qr');
        });

        Route::middleware('permission:access.manage.correspondence')->group(function (): void {
            Route::resource('correspondence', CorrespondenceController::class)->except(['edit', 'update']);
            Route::patch('correspondence/{correspondence}/deliver', [CorrespondenceController::class, 'markDelivered'])
                ->name('correspondence.deliver');
        });

        Route::middleware('permission:access.manage.guard_logs')->group(function (): void {
            Route::resource('guard_logs', GuardLogController::class)->except(['edit', 'update']);
        });

        Route::get('/reports', [ReportController::class, 'index'])
            ->middleware('permission:access.view.reports')
            ->name('reports.index');
    });
