<?php

/**
 * API Routes — All modules active
 * Each module includes its own routes.php
 */

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

require base_path('app/Modules/Iam/routes.php');
require base_path('app/Modules/Academic/routes.php');
require base_path('app/Modules/PeopleHr/routes.php');
require base_path('app/Modules/FinancePayroll/routes.php');
require base_path('app/Modules/PlatformServices/routes.php');
require base_path('app/Modules/CrmEnrollment/routes.php');
require base_path('app/Modules/Inventory/routes.php');
require base_path('app/Modules/FundingImpact/routes.php');

// Report generation endpoints
Route::middleware(['auth:sanctum', 'api.ratelimit:read'])->prefix('reports')->group(function () {
    Route::post('/financial', [ReportController::class, 'financialReport']);
    Route::get('/students/{studentId}/transcript', [ReportController::class, 'studentTranscript']);
    Route::get('/classes/{classId}/roster', [ReportController::class, 'classRoster']);
    Route::get('/certificates/{certificateId}', [ReportController::class, 'certificate']);
    Route::post('/payroll', [ReportController::class, 'payrollReport']);
});
