<?php

/**
 * IAM Module Routes
 * Included from routes/api.php
 */

use App\Modules\Iam\Http\Controllers\AuthController;
use App\Modules\Iam\Http\Controllers\BranchController;
use App\Modules\Iam\Http\Controllers\CampusController;
use App\Modules\Iam\Http\Controllers\OrganizationController;
use App\Modules\Iam\Http\Controllers\RoleController;
use App\Modules\Iam\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected auth routes
Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

// Organization management
Route::middleware('auth:sanctum')->prefix('organizations')->group(function () {
    Route::get('/', [OrganizationController::class, 'index']);
    Route::post('/', [OrganizationController::class, 'store']);
    Route::get('/{id}', [OrganizationController::class, 'show']);
    Route::patch('/{id}', [OrganizationController::class, 'update']);
    Route::delete('/{id}', [OrganizationController::class, 'destroy']);
});

// Campus management
Route::middleware('auth:sanctum')->prefix('campuses')->group(function () {
    Route::get('/', [CampusController::class, 'index']);
    Route::post('/', [CampusController::class, 'store']);
    Route::get('/{id}', [CampusController::class, 'show']);
    Route::patch('/{id}', [CampusController::class, 'update']);
    Route::delete('/{id}', [CampusController::class, 'destroy']);
});

// Branch management
Route::middleware('auth:sanctum')->prefix('branches')->group(function () {
    Route::get('/', [BranchController::class, 'index']);
    Route::post('/', [BranchController::class, 'store']);
    Route::get('/{id}', [BranchController::class, 'show']);
    Route::patch('/{id}', [BranchController::class, 'update']);
    Route::delete('/{id}', [BranchController::class, 'destroy']);
});

// User management
Route::middleware('auth:sanctum')->prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::patch('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});

// Roles & Permissions (read-only catalogs)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/roles', [RoleController::class, 'indexRoles']);
    Route::get('/permissions', [RoleController::class, 'indexPermissions']);
});
