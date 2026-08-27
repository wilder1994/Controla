<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CorrespondenceController;
use App\Http\Controllers\Api\PreAuthorizationController;
use App\Http\Controllers\Api\SupervisorFieldLogController;
use App\Http\Controllers\Api\SupervisorShiftController;
use App\Http\Controllers\Api\VisitorController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/supervision/login', [SupervisorShiftController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('pre-authorizations', PreAuthorizationController::class)->except(['update']);
    Route::get('correspondence', [CorrespondenceController::class, 'index']);
    Route::get('correspondence/{correspondence}', [CorrespondenceController::class, 'show']);

    Route::get('visitors/search', [VisitorController::class, 'search']);

    Route::middleware('supervisor.pro')->prefix('supervision')->group(function () {
        Route::get('/shifts/current', [SupervisorShiftController::class, 'current']);
        Route::get('/intake', [SupervisorShiftController::class, 'intake']);
        Route::get('/sites', [SupervisorShiftController::class, 'sites']);
        Route::get('/posts', [SupervisorShiftController::class, 'posts']);
        Route::get('/guards', [SupervisorShiftController::class, 'guards']);
        Route::get('/shift-photo/start-selfie', [SupervisorShiftController::class, 'startSelfie']);
        Route::post('/shifts/open', [SupervisorShiftController::class, 'open']);
        Route::post('/shifts/ping', [SupervisorShiftController::class, 'ping']);
        Route::post('/shifts/close', [SupervisorShiftController::class, 'close']);
        Route::post('/reviews', [SupervisorShiftController::class, 'review']);
        Route::get('/catalog', [SupervisorFieldLogController::class, 'catalog']);
        Route::post('/logs', [SupervisorFieldLogController::class, 'store']);
        Route::get('/recommendations', [SupervisorFieldLogController::class, 'recommendations']);
        Route::patch('/recommendations/{recommendation}', [SupervisorFieldLogController::class, 'updateRecommendation']);
    });
});
