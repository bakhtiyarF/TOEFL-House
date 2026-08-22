<?php

use App\Modules\FundingImpact\Http\Controllers\FundingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Donors
    Route::prefix('donors')->group(function () {
        Route::get('/', [FundingController::class, 'indexDonors']);
        Route::post('/', [FundingController::class, 'storeDonor']);
    });

    // Campaigns
    Route::prefix('funding-campaigns')->group(function () {
        Route::get('/', [FundingController::class, 'indexCampaigns']);
        Route::post('/', [FundingController::class, 'storeCampaign']);
    });

    // Donations
    Route::post('/donations', [FundingController::class, 'storeDonation']);

    // Scholarships
    Route::prefix('scholarships')->group(function () {
        Route::get('/', [FundingController::class, 'indexScholarships']);
        Route::post('/{scholarshipId}/awards', [FundingController::class, 'awardScholarship']);
    });

    // Impact Metrics
    Route::get('/impact-metrics', [FundingController::class, 'indexImpactMetrics']);
});
