<?php

use App\Modules\PeopleHr\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('teachers')->group(function () {
    Route::get('/', [TeacherController::class, 'index']);
    Route::post('/', [TeacherController::class, 'store']);
    Route::get('/{id}', [TeacherController::class, 'show']);
    Route::patch('/{id}', [TeacherController::class, 'update']);
    Route::delete('/{id}', [TeacherController::class, 'destroy']);
    Route::post('/{id}/transfer', [TeacherController::class, 'transfer']);

    // Evaluations (live per 06 spec)
    Route::get('/{id}/evaluations', [TeacherController::class, 'evaluations']);
    Route::post('/{id}/evaluations', [TeacherController::class, 'storeEvaluation']);
});

// Employees (full 06 spec support — CRUD + transfer, salary is delegated)
use App\Modules\PeopleHr\Http\Controllers\EmployeeController;

Route::middleware('auth:sanctum')->prefix('employees')->group(function () {
    Route::get('/', [EmployeeController::class, 'index']);
    Route::post('/', [EmployeeController::class, 'store']);
    Route::get('/{id}', [EmployeeController::class, 'show']);
    Route::patch('/{id}', [EmployeeController::class, 'update']);
    Route::delete('/{id}', [EmployeeController::class, 'destroy']);
    Route::post('/{id}/transfer', [EmployeeController::class, 'transfer']);
});
