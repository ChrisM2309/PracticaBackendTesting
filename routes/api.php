<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ReturnLoanController;
use Illuminate\Support\Facades\Route;

Route::post('v1/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('profile', [AuthController::class, 'profile']);

    // Books
    Route::get('books', [BookController::class, 'index']);
    Route::get('loans', [LoanController::class, 'index']);
    Route::post('loans', [LoanController::class, 'store']);
    Route::post('loans/{loan}/return', ReturnLoanController::class);

    // Hola, Soy Chris!! 
});
