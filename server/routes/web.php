<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'app' => 'TOEFL House ERP v3',
        'version' => '3.0',
        'status' => 'ok',
        'message' => 'API-only backend. Use /api/* endpoints.'
    ]);
});
