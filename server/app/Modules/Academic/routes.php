<?php

use App\Modules\Academic\Http\Controllers\StudentController;
use App\Modules\Academic\Http\Controllers\ClassController;
use App\Modules\Academic\Http\Controllers\SessionController;
use App\Modules\Academic\Http\Controllers\ProgramController;
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
        Route::post('/{id}/enroll', [StudentController::class, 'enroll']);
    });

    // Programs / Catalog
    Route::prefix('programs')->group(function () {
        Route::get('/', [ProgramController::class, 'index']);
        Route::post('/', [ProgramController::class, 'store']);
        Route::get('/{programId}/versions', [ProgramController::class, 'versions']);
    });

    // Classes
    Route::prefix('classes')->group(function () {
        Route::get('/', [ClassController::class, 'index']);
        Route::post('/', [ClassController::class, 'store']);
        Route::get('/{id}', [ClassController::class, 'show']);
        Route::patch('/{id}', [ClassController::class, 'update']);
    });

    // Sessions
    Route::prefix('classes/{classId}/sessions')->group(function () {
        Route::get('/', [SessionController::class, 'index']);
        Route::post('/', [SessionController::class, 'store']);
    });

    Route::prefix('sessions/{sessionId}')->group(function () {
        Route::get('/roster', [SessionController::class, 'roster']);
        Route::post('/attendance', [SessionController::class, 'updateAttendance']);
    });
});
