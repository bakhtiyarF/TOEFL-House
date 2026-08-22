<?php

use App\Modules\Academic\Http\Controllers\StudentController;
use App\Modules\Academic\Http\Controllers\ClassController;
use App\Modules\Academic\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Students
    Route::prefix('students')->group(function () {
        Route::get('/', [StudentController::class, 'index']);
        Route::post('/', [StudentController::class, 'store']);
        Route::get('/{id}', [StudentController::class, 'show']);
        Route::patch('/{id}', [StudentController::class, 'update']);
        Route::delete('/{id}', [StudentController::class, 'destroy']);
        Route::get('/{id}/journey', [StudentController::class, 'journey']);
    });

    // Classes
    Route::prefix('classes')->group(function () {
        Route::get('/', [ClassController::class, 'index']);
        Route::post('/', [ClassController::class, 'store']);
        Route::get('/{id}', [ClassController::class, 'show']);
        Route::patch('/{id}', [ClassController::class, 'update']);
    });

    // Sessions (nested under classes)
    Route::prefix('classes/{classId}/sessions')->group(function () {
        Route::get('/', [SessionController::class, 'index']);
        Route::post('/', [SessionController::class, 'store']);
    });

    // Session roster and attendance
    Route::prefix('sessions/{sessionId}')->group(function () {
        Route::get('/roster', [SessionController::class, 'roster']);
        Route::post('/attendance', [SessionController::class, 'updateAttendance']);
    });
});
