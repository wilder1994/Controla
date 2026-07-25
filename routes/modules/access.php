<?php

use App\Http\Controllers\Access\DashboardController;
use App\Http\Controllers\Access\LocationController;
use App\Http\Controllers\Access\VisitorController;
use App\Http\Controllers\Access\VehicleController;
use App\Http\Controllers\Access\VehicleAccessController;
use App\Http\Controllers\Access\AccessLogController;
use App\Http\Controllers\Access\PreAuthorizationController;
use App\Http\Controllers\Access\CorrespondenceController;
use App\Http\Controllers\Access\GuardLogController;
use App\Http\Controllers\Access\ReportController;
use App\Http\Controllers\Access\BuildingController;
use App\Http\Controllers\Access\HousingUnitController;
use App\Http\Controllers\Access\ResidentController;
use App\Http\Controllers\Access\OperationsController;
use App\Http\Controllers\Access\BlocklistController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'password.changed', 'active', 'tenancy.access'])->prefix('access')->name('access.')->group(function () {
    // Operations Hub
    Route::get('/operations', [OperationsController::class, 'index'])->name('operations');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Locations
    Route::resource('locations', LocationController::class)->except(['show']);

    // Buildings (Torres/Bloques)
    Route::resource('buildings', BuildingController::class)->except(['show']);

    // Housing Units (Apartamentos/Casas)
    Route::resource('housing_units', HousingUnitController::class)->except(['show']);
    Route::get('housing_units/by-building/{building}', [HousingUnitController::class, 'searchByBuildingJson'])->name('housing_units.by_building');

    // Visitors
    Route::resource('visitors', VisitorController::class);
    Route::get('visitors/search/json', [VisitorController::class, 'searchJson'])->name('visitors.search.json');
    Route::post('visitors/scan-register', [VisitorController::class, 'scanRegister'])->name('visitors.scan-register');

    // Residents
    Route::resource('residents', ResidentController::class);
    Route::get('residents/search/json', [ResidentController::class, 'searchJson'])->name('residents.search.json');
    Route::get('residents/housing-units/json', [ResidentController::class, 'searchHousingUnitsJson'])->name('residents.housing_units.json');
    Route::post('residents/{resident}/vehicles', [ResidentController::class, 'addVehicle'])->name('residents.vehicles.store');
    Route::delete('residents/{resident}/vehicles/{vehicle}', [ResidentController::class, 'removeVehicle'])->name('residents.vehicles.destroy');

    // Vehicles
    Route::resource('vehicles', VehicleController::class)->except(['show']);
    Route::get('vehicles/search/json', [VehicleController::class, 'searchJson'])->name('vehicles.search.json');
    Route::get('vehicles/search/resident/json', [VehicleController::class, 'searchResidentVehicleJson'])->name('vehicles.search.resident.json');

    // Vehicle Access (residentes/propietarios)
    Route::get('/vehicle-access', [VehicleAccessController::class, 'index'])->name('vehicle_access.index');
    Route::get('/vehicle-access/entry', [VehicleAccessController::class, 'entry'])->name('vehicle_access.entry');
    Route::post('/vehicle-access/entry', [VehicleAccessController::class, 'storeEntry'])->name('vehicle_access.entry.store');
    Route::patch('/vehicle-access/{accessLog}/exit', [VehicleAccessController::class, 'markExit'])->name('vehicle_access.exit');
    Route::get('/vehicle-access/search', [VehicleAccessController::class, 'searchVehicleJson'])->name('vehicle_access.search');

    // Access Logs (ingreso/salida)
    Route::get('/logs', [AccessLogController::class, 'index'])->name('logs.index');
    Route::get('/logs/entry', [AccessLogController::class, 'entry'])->name('logs.entry');
    Route::post('/logs/entry', [AccessLogController::class, 'storeEntry'])->name('logs.entry.store');
    Route::patch('/logs/{accessLog}/exit', [AccessLogController::class, 'markExit'])->name('logs.exit');

    // Pre-authorizations
    Route::resource('pre_authorizations', PreAuthorizationController::class)->except(['edit', 'update']);
    Route::get('pre_authorizations/{preAuthorization}/qr', [PreAuthorizationController::class, 'qr'])->name('pre_authorizations.qr');

    // Correspondence
    Route::resource('correspondence', CorrespondenceController::class)->except(['edit', 'update']);
    Route::patch('correspondence/{correspondence}/deliver', [CorrespondenceController::class, 'markDelivered'])->name('correspondence.deliver');

    // Guard Logs
    Route::resource('guard_logs', GuardLogController::class)->except(['edit', 'update']);
    Route::post('/guard_logs/panic', [GuardLogController::class, 'panic'])->name('guard_logs.panic');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Blocklist
    Route::get('/blocklist', [BlocklistController::class, 'index'])->name('blocklist.index');
    Route::get('/blocklist/create', [BlocklistController::class, 'create'])->name('blocklist.create');
    Route::post('/blocklist', [BlocklistController::class, 'store'])->name('blocklist.store');
    Route::get('/blocklist/search', [BlocklistController::class, 'searchJson'])->name('blocklist.search');
    Route::delete('/blocklist/{blocklist}', [BlocklistController::class, 'destroy'])->name('blocklist.destroy');

    // Bulk Exit
    Route::post('/logs/bulk-exit', [AccessLogController::class, 'bulkExit'])->name('logs.bulk-exit');
});
