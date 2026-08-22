<?php

use App\Modules\CrmEnrollment\Http\Controllers\VisitorController;
use App\Modules\CrmEnrollment\Http\Controllers\CampaignController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Visitors
    Route::prefix('visitors')->group(function () {
        Route::get('/', [VisitorController::class, 'index']);
        Route::post('/', [VisitorController::class, 'store']);
        Route::get('/{id}', [VisitorController::class, 'show']);
        Route::post('/{id}/followups', [VisitorController::class, 'addFollowup']);
        Route::get('/{id}/conversion-readiness', [VisitorController::class, 'conversionReadiness']);
        Route::post('/{id}/convert', [VisitorController::class, 'convert']);
    });

    // Campaigns
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [CampaignController::class, 'index']);
        Route::post('/', [CampaignController::class, 'store']);
        Route::patch('/{id}', [CampaignController::class, 'update']);
    });
});
