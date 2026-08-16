<?php

declare(strict_types=1);

use App\Http\Controllers\Platform\CompanyController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\DocumentController;
use App\Http\Controllers\Platform\PricingController;
use App\Http\Controllers\Platform\IdentityDocumentTypeController;
use App\Http\Controllers\Platform\StructureTypeController;
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

        Route::get('/companies/create', [CompanyController::class, 'create'])
            ->middleware('permission:platform.companies.manage')
            ->name('companies.create');

        Route::post('/companies', [CompanyController::class, 'store'])
            ->middleware('permission:platform.companies.manage')
            ->name('companies.store');

        Route::get('/companies/{company}', [CompanyController::class, 'show'])
            ->middleware('permission:platform.companies.view')
            ->name('companies.show');

        Route::get('/companies/{company}/historial', [CompanyController::class, 'historial'])
            ->middleware('permission:platform.companies.view')
            ->name('companies.historial');

        Route::post('/companies/{company}/enter', [CompanyController::class, 'enterAsSupport'])
            ->middleware('permission:platform.companies.manage')
            ->name('companies.enter');

        Route::post('/support/exit', [CompanyController::class, 'exitSupport'])
            ->middleware('permission:platform.companies.manage')
            ->name('support.exit');

        Route::post('/companies/{company}/payments/manual', [CompanyController::class, 'storeManualPayment'])
            ->middleware('permission:platform.companies.manage')
            ->name('companies.payment.manual');

        Route::post('/companies/{company}/membership/cancel', [CompanyController::class, 'cancelMembership'])
            ->middleware('permission:platform.companies.manage')
            ->name('companies.membership.cancel');

        Route::post('/companies/{company}/membership/undo-cancel', [CompanyController::class, 'undoMembershipCancellation'])
            ->middleware('permission:platform.companies.manage')
            ->name('companies.membership.undo-cancel');

        Route::post('/companies/{company}/package/schedule', [CompanyController::class, 'schedulePackageChange'])
            ->middleware('permission:platform.companies.manage')
            ->name('companies.package.schedule');

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

        Route::get('/documents/normativa/{corpus}/edit', [DocumentController::class, 'editNormativa'])
            ->middleware('permission:platform.documents.manage')
            ->name('documents.normativa.edit');

        Route::put('/documents/normativa/{corpus}', [DocumentController::class, 'publishNormativa'])
            ->middleware('permission:platform.documents.manage')
            ->name('documents.normativa.publish');

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

        Route::get('/settings/structure-types', [StructureTypeController::class, 'index'])
            ->middleware('permission:platform.settings.manage')
            ->name('settings.structure-types.index');

        Route::post('/settings/structure-types', [StructureTypeController::class, 'store'])
            ->middleware('permission:platform.settings.manage')
            ->name('settings.structure-types.store');

        Route::put('/settings/structure-types/{structureType}', [StructureTypeController::class, 'update'])
            ->middleware('permission:platform.settings.manage')
            ->name('settings.structure-types.update');

        Route::post('/settings/structure-types/{structureType}/move-up', [StructureTypeController::class, 'moveUp'])
            ->middleware('permission:platform.settings.manage')
            ->name('settings.structure-types.move-up');

        Route::post('/settings/structure-types/{structureType}/move-down', [StructureTypeController::class, 'moveDown'])
            ->middleware('permission:platform.settings.manage')
            ->name('settings.structure-types.move-down');

        Route::delete('/settings/structure-types/{structureType}', [StructureTypeController::class, 'destroy'])
            ->middleware('permission:platform.settings.manage')
            ->name('settings.structure-types.destroy');

        Route::get('/settings/document-types', [IdentityDocumentTypeController::class, 'index'])
            ->middleware('permission:platform.settings.manage')
            ->name('settings.document-types.index');

        Route::post('/settings/document-types', [IdentityDocumentTypeController::class, 'store'])
            ->middleware('permission:platform.settings.manage')
            ->name('settings.document-types.store');

        Route::put('/settings/document-types/{documentType}', [IdentityDocumentTypeController::class, 'update'])
            ->middleware('permission:platform.settings.manage')
            ->name('settings.document-types.update');

        Route::post('/settings/document-types/{documentType}/move-up', [IdentityDocumentTypeController::class, 'moveUp'])
            ->middleware('permission:platform.settings.manage')
            ->name('settings.document-types.move-up');

        Route::post('/settings/document-types/{documentType}/move-down', [IdentityDocumentTypeController::class, 'moveDown'])
            ->middleware('permission:platform.settings.manage')
            ->name('settings.document-types.move-down');

        Route::delete('/settings/document-types/{documentType}', [IdentityDocumentTypeController::class, 'destroy'])
            ->middleware('permission:platform.settings.manage')
            ->name('settings.document-types.destroy');
    });
