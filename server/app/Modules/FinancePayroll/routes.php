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

    // Bulk payroll processing (live from UI)
    Route::post('/payroll/process', [PaymentController::class, 'processPayroll']);

    // Payroll ledger history
    Route::get('/payroll/ledger', [PaymentController::class, 'payrollLedger']);

    // Expense requests (full 07 spec: create + approve/reject + budget impact)
    Route::prefix('expense-requests')->group(function () {
        Route::get('/', [\App\Modules\FinancePayroll\Http\Controllers\ExpenseController::class, 'index']);
        Route::post('/', [\App\Modules\FinancePayroll\Http\Controllers\ExpenseController::class, 'store']);
        Route::post('/{id}/approve', [\App\Modules\FinancePayroll\Http\Controllers\ExpenseController::class, 'approve']);
        Route::post('/{id}/reject', [\App\Modules\FinancePayroll\Http\Controllers\ExpenseController::class, 'reject']);
    });

    // Invoices (07 spec: full lifecycle)
    Route::prefix('invoices')->group(function () {
        Route::get('/', [\App\Modules\FinancePayroll\Http\Controllers\InvoiceController::class, 'index']);
        Route::post('/', [\App\Modules\FinancePayroll\Http\Controllers\InvoiceController::class, 'store']);
        Route::post('/{id}/mark-paid', [\App\Modules\FinancePayroll\Http\Controllers\InvoiceController::class, 'markPaid']);
    });
});
