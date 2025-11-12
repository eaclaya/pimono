<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [UserController::class, 'profile']);
    Route::apiResource('transactions', TransactionController::class);
    Route::get('/receivers', [UserController::class, 'receivers']);

    Route::post('/broadcasting/auth', [BroadcastController::class, 'store']);
});

Route::post('/auth/login', [AuthController::class, 'login']);
