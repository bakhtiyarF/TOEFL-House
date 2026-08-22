<?php

use App\Modules\CrmEnrollment\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('visitors')->group(function () {
        Route::get('/', [VisitorController::class, 'index']);
        Route::post('/', [VisitorController::class, 'store']);
        Route::get('/{id}', [VisitorController::class, 'show']);
        Route::post('/{id}/followups', [VisitorController::class, 'addFollowup']);
        Route::get('/{id}/conversion-readiness', [VisitorController::class, 'conversionReadiness']);
        Route::post('/{id}/convert', [VisitorController::class, 'convert']);
    });
});
