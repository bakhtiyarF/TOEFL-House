<?php

use App\Modules\PlatformServices\Http\Controllers\RuleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Rule definitions
    Route::prefix('rules')->group(function () {
        Route::get('/', [RuleController::class, 'index']);
        Route::post('/', [RuleController::class, 'store']);
        Route::get('/{id}', [RuleController::class, 'show']);
        Route::get('/{id}/evaluate', [RuleController::class, 'evaluate']);
    });
});
