<?php

use App\Modules\PlatformServices\Http\Controllers\RuleController;
use App\Modules\PlatformServices\Http\Controllers\NotificationController;
use App\Modules\PlatformServices\Http\Controllers\AuditLogController;
use App\Modules\PlatformServices\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Rule definitions
    Route::prefix('rules')->group(function () {
        Route::get('/', [RuleController::class, 'index']);
        Route::post('/', [RuleController::class, 'store']);
        Route::get('/{id}', [RuleController::class, 'show']);
        Route::get('/{id}/evaluate', [RuleController::class, 'evaluate']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
    });

    // Audit logs (read-only, requires Audit.View permission)
    Route::get('/audit-logs', [AuditLogController::class, 'index']);

    // System settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::get('/{key}', [SettingsController::class, 'show']);
        Route::patch('/{key}', [SettingsController::class, 'update']);
        Route::post('/batch', [SettingsController::class, 'batchUpdate']);
    });
});

use App\Modules\PlatformServices\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('search')->group(function () {
    Route::get('/', [SearchController::class, 'globalSearch']);
});
