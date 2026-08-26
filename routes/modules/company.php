<?php

declare(strict_types=1);

use App\Http\Controllers\Company\BillingCheckoutController;
use App\Http\Controllers\Company\BillingController;
use App\Http\Controllers\Company\ClientController;
use App\Http\Controllers\Company\CollaboratorTypeController;
use App\Http\Controllers\Company\DashboardController;
use App\Http\Controllers\Company\EmployeeController;
use App\Http\Controllers\Company\JobTitleController;
use App\Http\Controllers\Company\PorteriaController;
use App\Http\Controllers\Company\SettingsController;
use App\Http\Controllers\Company\SupervisionMapController;
use App\Http\Controllers\Company\SupervisorChecklistItemController;
use App\Http\Controllers\Company\SupervisorShiftTemplateController;
use App\Http\Controllers\Company\SupervisorZoneController;
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

        Route::post('/billing/supervision', [BillingController::class, 'updateSupervisionPackage'])
            ->middleware('permission:company.dashboard')
            ->name('billing.supervision.update');

        Route::get('/settings', [SettingsController::class, 'edit'])
            ->middleware('permission:company.settings.manage')
            ->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])
            ->middleware('permission:company.settings.manage')
            ->name('settings.update');

        Route::middleware('permission:company.settings.manage')->group(function () {
            Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::get('/employees/template', [EmployeeController::class, 'downloadTemplate'])->name('employees.template');
            Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
            Route::post('/employees/import/preview', [EmployeeController::class, 'storeImportPreview'])->name('employees.import.preview.store');
            Route::get('/employees/import/preview', [EmployeeController::class, 'showImportPreview'])->name('employees.import.preview');
            Route::post('/employees/import/commit', [EmployeeController::class, 'commitImport'])->name('employees.import.commit');
            Route::post('/employees/import/cancel', [EmployeeController::class, 'cancelImport'])->name('employees.import.cancel');
            Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
            Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::post('/employees/{employee}/archive', [EmployeeController::class, 'archive'])->name('employees.archive');
            Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
            Route::post('/employees/{employee}/access', [EmployeeController::class, 'grantAccess'])
                ->middleware('permission:company.users.assign')
                ->name('employees.access');

            Route::get('/job-titles', [JobTitleController::class, 'index'])->name('job-titles.index');
            Route::post('/job-titles', [JobTitleController::class, 'store'])->name('job-titles.store');
            Route::put('/job-titles/{jobTitle}', [JobTitleController::class, 'update'])->name('job-titles.update');
            Route::delete('/job-titles/{jobTitle}', [JobTitleController::class, 'destroy'])->name('job-titles.destroy');

            Route::get('/collaborator-types', [CollaboratorTypeController::class, 'index'])->name('collaborator-types.index');
            Route::post('/collaborator-types', [CollaboratorTypeController::class, 'store'])->name('collaborator-types.store');
            Route::put('/collaborator-types/{collaboratorType}', [CollaboratorTypeController::class, 'update'])->name('collaborator-types.update');
            Route::delete('/collaborator-types/{collaboratorType}', [CollaboratorTypeController::class, 'destroy'])->name('collaborator-types.destroy');

            Route::get('/supervision-zones', [SupervisorZoneController::class, 'index'])->name('supervision-zones.index');
            Route::post('/supervision-zones', [SupervisorZoneController::class, 'store'])->name('supervision-zones.store');
            Route::put('/supervision-zones/{zone}', [SupervisorZoneController::class, 'update'])->name('supervision-zones.update');
            Route::delete('/supervision-zones/{zone}', [SupervisorZoneController::class, 'destroy'])->name('supervision-zones.destroy');

            Route::get('/supervision-shifts', [SupervisorShiftTemplateController::class, 'index'])->name('supervision-shifts.index');
            Route::post('/supervision-shifts', [SupervisorShiftTemplateController::class, 'store'])->name('supervision-shifts.store');
            Route::put('/supervision-shifts/{template}', [SupervisorShiftTemplateController::class, 'update'])->name('supervision-shifts.update');
            Route::delete('/supervision-shifts/{template}', [SupervisorShiftTemplateController::class, 'destroy'])->name('supervision-shifts.destroy');

            Route::get('/supervision-preop', [SupervisorChecklistItemController::class, 'index'])->name('supervision-preop.index');
            Route::post('/supervision-preop', [SupervisorChecklistItemController::class, 'store'])->name('supervision-preop.store');
            Route::put('/supervision-preop/{item}', [SupervisorChecklistItemController::class, 'update'])->name('supervision-preop.update');
            Route::delete('/supervision-preop/{item}', [SupervisorChecklistItemController::class, 'destroy'])->name('supervision-preop.destroy');
        });

        Route::get('/supervision', [SupervisionMapController::class, 'index'])
            ->middleware('permission:company.supervision.view')
            ->name('supervision.index');
        Route::get('/supervision/informe.pptx', [SupervisionMapController::class, 'report'])
            ->middleware('permission:company.supervision.view')
            ->name('supervision.report');

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

        Route::get('/clients/template', [ClientController::class, 'downloadTemplate'])
            ->middleware('permission:company.clients.manage')
            ->name('clients.template');
        Route::post('/clients/import/preview', [ClientController::class, 'storeImportPreview'])
            ->middleware('permission:company.clients.manage')
            ->name('clients.import.preview.store');
        Route::get('/clients/import/preview', [ClientController::class, 'showImportPreview'])
            ->middleware('permission:company.clients.manage')
            ->name('clients.import.preview');
        Route::post('/clients/import/commit', [ClientController::class, 'commitImport'])
            ->middleware('permission:company.clients.manage')
            ->name('clients.import.commit');
        Route::post('/clients/import/cancel', [ClientController::class, 'cancelImport'])
            ->middleware('permission:company.clients.manage')
            ->name('clients.import.cancel');

        Route::resource('clients', ClientController::class);
    });
