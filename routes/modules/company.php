<?php

declare(strict_types=1);

use App\Http\Controllers\Company\BillingCheckoutController;
use App\Http\Controllers\Company\BillingController;
use App\Http\Controllers\Company\ClientAccessPointController;
use App\Http\Controllers\Company\ClientController;
use App\Http\Controllers\Company\ClientInstallationController;
use App\Http\Controllers\Company\ClientSupervisorPostController;
use App\Http\Controllers\Company\CollaboratorTypeController;
use App\Http\Controllers\Company\DashboardController;
use App\Http\Controllers\Company\DownloadsController;
use App\Http\Controllers\Company\EmployeeController;
use App\Http\Controllers\Company\PersonnelDocumentController;
use App\Http\Controllers\Company\JobTitleController;
use App\Http\Controllers\Company\PorteriaController;
use App\Http\Controllers\Company\SettingsController;
use App\Http\Controllers\Company\StructureTypeController;
use App\Http\Controllers\Company\SupervisionFieldSheetController;
use App\Http\Controllers\Company\SupervisionMapController;
use App\Http\Controllers\Company\SupervisorAlarmTypeController;
use App\Http\Controllers\Company\SupervisorChecklistItemController;
use App\Http\Controllers\Company\SupervisorControlBookTypeController;
use App\Http\Controllers\Company\SupervisorDocumentTypeController;
use App\Http\Controllers\Company\SupervisorRiskTypeController;
use App\Http\Controllers\Company\SupervisorShiftTemplateController;
use App\Http\Controllers\Company\SupervisorSupportTypeController;
use App\Http\Controllers\Company\SupervisorWeaponBrandController;
use App\Http\Controllers\Company\SupervisorWeaponTypeController;
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
        Route::get('/settings/logo', [SettingsController::class, 'logo'])
            ->middleware('permission:company.settings.manage')
            ->name('settings.logo');
        Route::put('/settings', [SettingsController::class, 'update'])
            ->middleware('permission:company.settings.manage')
            ->name('settings.update');

        Route::middleware('permission:company.settings.manage')->group(function () {
            Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::get('/employees/template', [EmployeeController::class, 'downloadTemplate'])->name('employees.template');
            Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('/employees/catalog-starter', [EmployeeController::class, 'storeCatalogStarter'])->name('employees.catalog-starter');
            Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
            Route::post('/employees/import/preview', [EmployeeController::class, 'storeImportPreview'])->name('employees.import.preview.store');
            Route::get('/employees/import/preview', [EmployeeController::class, 'showImportPreview'])->name('employees.import.preview');
            Route::post('/employees/import/commit', [EmployeeController::class, 'commitImport'])->name('employees.import.commit');
            Route::post('/employees/import/cancel', [EmployeeController::class, 'cancelImport'])->name('employees.import.cancel');
            Route::get('/employees/lookup', [EmployeeController::class, 'lookup'])->name('employees.lookup');
            Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
            Route::get('/employees/{employee}/photo', [EmployeeController::class, 'photo'])->name('employees.photo');
            Route::post('/employees/{employee}/photo', [EmployeeController::class, 'storePhoto'])->name('employees.photo.store');
            Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::post('/employees/{employee}/archive', [EmployeeController::class, 'archive'])->name('employees.archive');
            Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
            Route::post('/employees/{employee}/reassign', [EmployeeController::class, 'reassign'])->name('employees.reassign');

            Route::get('/documents', [PersonnelDocumentController::class, 'index'])->name('personnel-documents.index');
            Route::get('/documents/file/{document}/preview', [PersonnelDocumentController::class, 'preview'])->name('personnel-documents.preview');
            Route::get('/documents/file/{document}/download', [PersonnelDocumentController::class, 'download'])->name('personnel-documents.download');
            Route::delete('/documents/file/{document}', [PersonnelDocumentController::class, 'destroy'])->name('personnel-documents.destroy');
            Route::get('/documents/{employee}', [PersonnelDocumentController::class, 'folder'])->name('personnel-documents.folder');
            Route::post('/documents/{employee}/batches', [PersonnelDocumentController::class, 'storeBatch'])->name('personnel-documents.batch.create');
            Route::get('/documents/{employee}/batches/{batch}', [PersonnelDocumentController::class, 'batchIndex'])->name('personnel-documents.batch.index');
            Route::post('/documents/{employee}/batches/{batch}', [PersonnelDocumentController::class, 'storeBatchIndex'])->name('personnel-documents.batch.store');
            Route::get('/documents/{employee}/batches/{batch}/preview', [PersonnelDocumentController::class, 'previewBatch'])->name('personnel-documents.batch.preview');
            Route::post('/documents/{employee}/folders/{folder}/na', [PersonnelDocumentController::class, 'markNa'])->name('personnel-documents.na');

            Route::get('/job-titles', [JobTitleController::class, 'index'])->name('job-titles.index');
            Route::post('/job-titles', [JobTitleController::class, 'store'])->name('job-titles.store');
            Route::put('/job-titles/{jobTitle}', [JobTitleController::class, 'update'])->name('job-titles.update');
            Route::delete('/job-titles/{jobTitle}', [JobTitleController::class, 'destroy'])->name('job-titles.destroy');

            Route::get('/collaborator-types', [CollaboratorTypeController::class, 'index'])->name('collaborator-types.index');
            Route::post('/collaborator-types', [CollaboratorTypeController::class, 'store'])->name('collaborator-types.store');
            Route::put('/collaborator-types/{collaboratorType}', [CollaboratorTypeController::class, 'update'])->name('collaborator-types.update');
            Route::delete('/collaborator-types/{collaboratorType}', [CollaboratorTypeController::class, 'destroy'])->name('collaborator-types.destroy');

            Route::get('/structure-types', [StructureTypeController::class, 'index'])->name('structure-types.index');
            Route::post('/structure-types', [StructureTypeController::class, 'store'])->name('structure-types.store');
            Route::put('/structure-types/{structureType}', [StructureTypeController::class, 'update'])->name('structure-types.update');
            Route::delete('/structure-types/{structureType}', [StructureTypeController::class, 'destroy'])->name('structure-types.destroy');

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

            Route::get('/supervision-document-types', [SupervisorDocumentTypeController::class, 'index'])->name('supervision-document-types.index');
            Route::post('/supervision-document-types', [SupervisorDocumentTypeController::class, 'store'])->name('supervision-document-types.store');
            Route::put('/supervision-document-types/{documentType}', [SupervisorDocumentTypeController::class, 'update'])->name('supervision-document-types.update');
            Route::delete('/supervision-document-types/{documentType}', [SupervisorDocumentTypeController::class, 'destroy'])->name('supervision-document-types.destroy');

            Route::get('/supervision-control-book-types', [SupervisorControlBookTypeController::class, 'index'])->name('supervision-control-book-types.index');
            Route::post('/supervision-control-book-types', [SupervisorControlBookTypeController::class, 'store'])->name('supervision-control-book-types.store');
            Route::put('/supervision-control-book-types/{controlBookType}', [SupervisorControlBookTypeController::class, 'update'])->name('supervision-control-book-types.update');
            Route::delete('/supervision-control-book-types/{controlBookType}', [SupervisorControlBookTypeController::class, 'destroy'])->name('supervision-control-book-types.destroy');

            Route::get('/supervision-weapon-types', [SupervisorWeaponTypeController::class, 'index'])->name('supervision-weapon-types.index');
            Route::post('/supervision-weapon-types', [SupervisorWeaponTypeController::class, 'store'])->name('supervision-weapon-types.store');
            Route::put('/supervision-weapon-types/{weaponType}', [SupervisorWeaponTypeController::class, 'update'])->name('supervision-weapon-types.update');
            Route::delete('/supervision-weapon-types/{weaponType}', [SupervisorWeaponTypeController::class, 'destroy'])->name('supervision-weapon-types.destroy');

            Route::get('/supervision-weapon-brands', [SupervisorWeaponBrandController::class, 'index'])->name('supervision-weapon-brands.index');
            Route::post('/supervision-weapon-brands', [SupervisorWeaponBrandController::class, 'store'])->name('supervision-weapon-brands.store');
            Route::put('/supervision-weapon-brands/{weaponBrand}', [SupervisorWeaponBrandController::class, 'update'])->name('supervision-weapon-brands.update');
            Route::delete('/supervision-weapon-brands/{weaponBrand}', [SupervisorWeaponBrandController::class, 'destroy'])->name('supervision-weapon-brands.destroy');

            Route::get('/supervision-risk-types', [SupervisorRiskTypeController::class, 'index'])->name('supervision-risk-types.index');
            Route::post('/supervision-risk-types', [SupervisorRiskTypeController::class, 'store'])->name('supervision-risk-types.store');
            Route::put('/supervision-risk-types/{riskType}', [SupervisorRiskTypeController::class, 'update'])->name('supervision-risk-types.update');
            Route::delete('/supervision-risk-types/{riskType}', [SupervisorRiskTypeController::class, 'destroy'])->name('supervision-risk-types.destroy');

            Route::get('/supervision-alarm-types', [SupervisorAlarmTypeController::class, 'index'])->name('supervision-alarm-types.index');
            Route::post('/supervision-alarm-types', [SupervisorAlarmTypeController::class, 'store'])->name('supervision-alarm-types.store');
            Route::put('/supervision-alarm-types/{alarmType}', [SupervisorAlarmTypeController::class, 'update'])->name('supervision-alarm-types.update');
            Route::delete('/supervision-alarm-types/{alarmType}', [SupervisorAlarmTypeController::class, 'destroy'])->name('supervision-alarm-types.destroy');

            Route::get('/supervision-support-types', [SupervisorSupportTypeController::class, 'index'])->name('supervision-support-types.index');
            Route::post('/supervision-support-types', [SupervisorSupportTypeController::class, 'store'])->name('supervision-support-types.store');
            Route::put('/supervision-support-types/{supportType}', [SupervisorSupportTypeController::class, 'update'])->name('supervision-support-types.update');
            Route::delete('/supervision-support-types/{supportType}', [SupervisorSupportTypeController::class, 'destroy'])->name('supervision-support-types.destroy');
        });

        Route::get('/supervision', [SupervisionMapController::class, 'index'])
            ->middleware('permission:company.supervision.view')
            ->name('supervision.index');
        Route::get('/supervision/live.json', [SupervisionMapController::class, 'liveFeed'])
            ->middleware('permission:company.supervision.view')
            ->name('supervision.live-feed');
        Route::get('/supervision/turnos/{shift}/ruta', [SupervisionMapController::class, 'snappedRoute'])
            ->middleware('permission:company.supervision.view')
            ->name('supervision.snapped-route');
        Route::get('/supervision/informe.pptx', [SupervisionMapController::class, 'report'])
            ->middleware('permission:company.supervision.view')
            ->name('supervision.report');
        Route::get('/supervision/fichas/{kind}/{id}', [SupervisionFieldSheetController::class, 'show'])
            ->middleware('permission:company.supervision.view')
            ->name('supervision.sheets.show');

        Route::get('/descargas', [DownloadsController::class, 'index'])
            ->middleware('permission:company.dashboard')
            ->name('downloads.index');

        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:company.users.assign')
            ->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])
            ->middleware('permission:company.users.assign')
            ->name('users.create');
        Route::get('/users/employee-search', [UserController::class, 'searchEmployees'])
            ->middleware('permission:company.users.assign')
            ->name('users.employee-search');
        Route::post('/users/credentials-preview', [UserController::class, 'previewCredentials'])
            ->middleware('permission:company.users.assign')
            ->name('users.credentials-preview');
        Route::get('/users/installations', [UserController::class, 'installations'])
            ->middleware('permission:company.users.assign')
            ->name('users.installations');
        Route::post('/users', [UserController::class, 'store'])
            ->middleware('permission:company.users.assign')
            ->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])
            ->middleware('permission:company.users.assign')
            ->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])
            ->middleware('permission:company.users.assign')
            ->name('users.update');
        Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])
            ->middleware('permission:company.users.assign')
            ->name('users.deactivate');
        Route::post('/users/{user}/reactivate', [UserController::class, 'reactivate'])
            ->middleware('permission:company.users.assign')
            ->name('users.reactivate');

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

        Route::post('/clients/{client}/installations', [ClientInstallationController::class, 'store'])
            ->middleware('permission:company.clients.manage')
            ->name('clients.installations.store');
        Route::put('/clients/{client}/installations/{installation}', [ClientInstallationController::class, 'update'])
            ->middleware('permission:company.clients.manage')
            ->name('clients.installations.update');
        Route::delete('/clients/{client}/installations/{installation}', [ClientInstallationController::class, 'destroy'])
            ->middleware('permission:company.clients.manage')
            ->name('clients.installations.destroy');

        Route::post('/clients/{client}/locations', [ClientAccessPointController::class, 'store'])
            ->middleware('permission:company.clients.manage')
            ->name('clients.locations.store');
        Route::put('/clients/{client}/locations/{location}', [ClientAccessPointController::class, 'update'])
            ->middleware('permission:company.clients.manage')
            ->name('clients.locations.update');
        Route::delete('/clients/{client}/locations/{location}', [ClientAccessPointController::class, 'destroy'])
            ->middleware('permission:company.clients.manage')
            ->name('clients.locations.destroy');

        Route::post('/clients/{client}/posts', [ClientSupervisorPostController::class, 'store'])
            ->middleware('permission:company.clients.manage')
            ->name('clients.posts.store');
        Route::put('/clients/{client}/posts/{post}', [ClientSupervisorPostController::class, 'update'])
            ->middleware('permission:company.clients.manage')
            ->name('clients.posts.update');
        Route::delete('/clients/{client}/posts/{post}', [ClientSupervisorPostController::class, 'destroy'])
            ->middleware('permission:company.clients.manage')
            ->name('clients.posts.destroy');

        Route::resource('clients', ClientController::class);
    });
