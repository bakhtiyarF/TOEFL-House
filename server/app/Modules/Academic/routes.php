<?php

use App\Modules\Academic\Http\Controllers\StudentController;
use App\Modules\Academic\Http\Controllers\ClassController;
use App\Modules\Academic\Http\Controllers\SessionController;
use App\Modules\Academic\Http\Controllers\ProgramController;
use App\Modules\Academic\Http\Controllers\CertificateController;
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

    // Homework
    Route::prefix('classes/{classId}/homework')->group(function () {
        Route::get('/', [\App\Modules\Academic\Http\Controllers\HomeworkController::class, 'index']);
        Route::post('/', [\App\Modules\Academic\Http\Controllers\HomeworkController::class, 'store']);
    });
    Route::patch('/homework/{homeworkId}', [\App\Modules\Academic\Http\Controllers\HomeworkController::class, 'update']);

    // Exams
    Route::prefix('classes/{classId}/exams')->group(function () {
        Route::get('/', [\App\Modules\Academic\Http\Controllers\ExamController::class, 'index']);
        Route::post('/', [\App\Modules\Academic\Http\Controllers\ExamController::class, 'store']);
    });
    Route::get('/exams/{examId}/results', [\App\Modules\Academic\Http\Controllers\ExamController::class, 'results']);
    Route::post('/exams/{examId}/results', [\App\Modules\Academic\Http\Controllers\ExamController::class, 'storeResult']);

    // Student-level assessments
    Route::get('/students/{studentId}/homework', [StudentController::class, 'homework']);
    Route::get('/students/{studentId}/exams', [StudentController::class, 'exams']);
    Route::post('/homework/{homeworkId}/mark-done', [\App\Modules\Academic\Http\Controllers\HomeworkController::class, 'markDone']);

    // Promotion (live)
    Route::get('/students/{studentId}/promotion-recommend', [\App\Modules\Academic\Http\Controllers\PromotionController::class, 'recommend']);
    Route::get('/program-versions/{programVersionId}/promotion-rules', [\App\Modules\Academic\Http\Controllers\PromotionController::class, 'rules']);
    Route::post('/students/{studentId}/promote', [\App\Modules\Academic\Http\Controllers\PromotionController::class, 'promote']);

    // Certificates (live — Certificate.Issue for designer + head_of_department per 14_ROLE + navRegistry)
    Route::prefix('certificates')->group(function () {
        Route::get('/', [CertificateController::class, 'index']);
        Route::get('/{id}', [CertificateController::class, 'show']);
        Route::post('/students/{studentId}/issue', [CertificateController::class, 'issue']);
        Route::post('/{id}/revoke', [CertificateController::class, 'revoke']);
    });
});
