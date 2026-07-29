<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Automation\AutomationController;
use App\Http\Controllers\Api\V1\Branding\BrandingAssetController;
use App\Http\Controllers\Api\V1\Channel\ChannelController;
use App\Http\Controllers\Api\V1\Dashboard\DashboardController;
use App\Http\Controllers\Api\V1\Media\MediaAssetController;
use App\Http\Controllers\Api\V1\Playlist\PlaylistController;
use App\Http\Controllers\Api\V1\Scheduling\ScheduleConflictController;
use App\Http\Controllers\Api\V1\Scheduling\ScheduleController;
use App\Http\Controllers\Api\V1\System\SystemController;
use App\Http\Controllers\Api\V1\Taxonomy\MediaCategoryController;
use App\Http\Controllers\Api\V1\Taxonomy\MediaTagController;
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
        Route::get('automation/control-center', [AutomationController::class, 'controlCenter']);
        Route::post('automation/tick', [AutomationController::class, 'tick']);

        Route::get('dashboard/summary', [DashboardController::class, 'summary']);
        Route::get('dashboard/vmix-status', [DashboardController::class, 'vmixStatus']);
        Route::get('dashboard/system-status', [DashboardController::class, 'systemStatus']);

        Route::get('channels', [ChannelController::class, 'index']);
        Route::post('channels', [ChannelController::class, 'store']);
        Route::get('channels/{uuid}', [ChannelController::class, 'show']);
        Route::put('channels/{uuid}', [ChannelController::class, 'update']);
        Route::delete('channels/{uuid}', [ChannelController::class, 'destroy']);

        Route::get('branding-assets', [BrandingAssetController::class, 'index']);
        Route::post('branding-assets/logos', [BrandingAssetController::class, 'storeLogo']);
        Route::post('branding-assets/announcements', [BrandingAssetController::class, 'storeAnnouncement']);
        Route::put('branding-assets/{uuid}', [BrandingAssetController::class, 'update']);
        Route::delete('branding-assets/{uuid}', [BrandingAssetController::class, 'destroy']);
        Route::get('branding-assets/{uuid}/preview', [BrandingAssetController::class, 'preview']);

        Route::get('playlists', [PlaylistController::class, 'index']);
        Route::post('playlists', [PlaylistController::class, 'store']);
        Route::get('playlists/{uuid}', [PlaylistController::class, 'show']);
        Route::put('playlists/{uuid}', [PlaylistController::class, 'update']);
        Route::delete('playlists/{uuid}', [PlaylistController::class, 'destroy']);

        Route::get('media-assets', [MediaAssetController::class, 'index']);
        Route::post('media-assets', [MediaAssetController::class, 'store']);
        Route::get('media-assets/browser', [MediaAssetController::class, 'browser']);
        Route::post('media-assets/import-folder/preview', [MediaAssetController::class, 'previewFolderImport']);
        Route::post('media-assets/import-folder', [MediaAssetController::class, 'importFolder']);
        Route::post('media-assets/upload-single', [MediaAssetController::class, 'uploadSingle']);
        Route::post('media-assets/upload-folder', [MediaAssetController::class, 'uploadFolder']);
        Route::get('media-assets/{uuid}', [MediaAssetController::class, 'show']);
        Route::put('media-assets/{uuid}', [MediaAssetController::class, 'update']);
        Route::delete('media-assets/{uuid}', [MediaAssetController::class, 'destroy']);
        Route::get('media-assets/{uuid}/preview', [MediaAssetController::class, 'preview']);

        Route::get('schedules', [ScheduleController::class, 'index']);
        Route::post('schedules', [ScheduleController::class, 'store']);
        Route::post('schedules/duplicate-day', [ScheduleController::class, 'duplicateDay']);
        Route::post('schedules/duplicate-week', [ScheduleController::class, 'duplicateWeek']);
        Route::get('schedules/{uuid}', [ScheduleController::class, 'show']);
        Route::put('schedules/{uuid}', [ScheduleController::class, 'update']);
        Route::delete('schedules/{uuid}', [ScheduleController::class, 'destroy']);
        Route::get('schedule-conflicts', [ScheduleConflictController::class, 'index']);

        Route::get('taxonomy/categories', [MediaCategoryController::class, 'index']);
        Route::post('taxonomy/categories', [MediaCategoryController::class, 'store']);
        Route::get('taxonomy/categories/{uuid}', [MediaCategoryController::class, 'show']);
        Route::put('taxonomy/categories/{uuid}', [MediaCategoryController::class, 'update']);
        Route::delete('taxonomy/categories/{uuid}', [MediaCategoryController::class, 'destroy']);

        Route::get('taxonomy/tags', [MediaTagController::class, 'index']);
        Route::post('taxonomy/tags', [MediaTagController::class, 'store']);
        Route::get('taxonomy/tags/{uuid}', [MediaTagController::class, 'show']);
        Route::put('taxonomy/tags/{uuid}', [MediaTagController::class, 'update']);
        Route::delete('taxonomy/tags/{uuid}', [MediaTagController::class, 'destroy']);

        Route::get('system/check', [SystemController::class, 'check']);
        Route::get('system/requirements', [SystemController::class, 'requirements']);
        Route::get('system/health', [SystemController::class, 'health']);
        Route::get('system/vmix', [SystemController::class, 'vmix']);
        Route::get('system/media-root', [SystemController::class, 'mediaRoot']);
        Route::put('system/media-root', [SystemController::class, 'updateMediaRoot']);
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
