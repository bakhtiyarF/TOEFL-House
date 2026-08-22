<?php

use App\Modules\Inventory\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('books')->group(function () {
        Route::get('/', [BookController::class, 'index']);
        Route::post('/', [BookController::class, 'store']);
        Route::post('/{id}/restock', [BookController::class, 'restock']);
        Route::post('/{id}/sell', [BookController::class, 'sell']);
    });

    Route::prefix('book-sales')->group(function () {
        Route::get('/', [BookController::class, 'sales']);
        Route::post('/{saleId}/refund', [BookController::class, 'refund']);
    });
});
