<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('transactions', TransactionController::class);
});