<?php

use App\Modules\FinancePayroll\Http\Controllers\PaymentController;
use App\Modules\FinancePayroll\Http\Controllers\BudgetController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Payments
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/', [PaymentController::class, 'store']);
    });

    // Student finance summary
    Route::get('/students/{studentId}/finance-summary', [PaymentController::class, 'studentFinanceSummary']);

    // Delegated teacher salary endpoints (06 §6)
    Route::prefix('teachers/{teacherId}')->group(function () {
        Route::get('/computed-salary', [PaymentController::class, 'teacherComputedSalary']);
        Route::post('/pay-salary', [PaymentController::class, 'payTeacherSalary']);
    });

    // Budget management
    Route::prefix('budget-lines')->group(function () {
        Route::get('/', [BudgetController::class, 'index']);
        Route::post('/', [BudgetController::class, 'store']);
        Route::patch('/{id}', [BudgetController::class, 'update']);
    });

    // Budget overview for BOS dashboard (07 §8)
    Route::get('/budget/overview', [BudgetController::class, 'overview']);
});
