<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/**
 * Health Check Routes
 * 
 * Public endpoints for monitoring and load balancers
 */

Route::prefix('health')->group(function () {
    Route::get('/ping', [HealthController::class, 'ping']);
    Route::get('/', [HealthController::class, 'health']);
    Route::get('/ready', [HealthController::class, 'ready']);
    Route::get('/live', [HealthController::class, 'live']);
});
