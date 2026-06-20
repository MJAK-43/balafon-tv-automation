<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Dashboard\DashboardController;
use App\Http\Controllers\Api\V1\System\SystemController;
use App\Http\Controllers\Api\V1\Vmix\VmixController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('dashboard/summary', [DashboardController::class, 'summary']);
        Route::get('dashboard/vmix-status', [DashboardController::class, 'vmixStatus']);
        Route::get('dashboard/system-status', [DashboardController::class, 'systemStatus']);

        Route::get('system/check', [SystemController::class, 'check']);
        Route::get('system/requirements', [SystemController::class, 'requirements']);
        Route::get('system/health', [SystemController::class, 'health']);
        Route::get('system/vmix', [SystemController::class, 'vmix']);
        Route::post('system/run-diagnostic', [SystemController::class, 'runDiagnostic']);

        Route::get('vmix/connections', [VmixController::class, 'connections']);
        Route::put('vmix/connections/{uuid}', [VmixController::class, 'updateConnection']);
        Route::get('vmix/status', [VmixController::class, 'status']);
        Route::get('vmix/inputs', [VmixController::class, 'inputs']);
        Route::post('vmix/connections/{uuid}/test', [VmixController::class, 'testConnection']);
        Route::get('vmix/connections/{uuid}/status', [VmixController::class, 'connectionStatus']);
        Route::post('vmix/play-test', [VmixController::class, 'playTest']);
    });
});
