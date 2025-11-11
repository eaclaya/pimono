<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('transactions', TransactionController::class);
    Route::get('/recipients', [UserController::class, 'recipients']);
});

Route::post('/auth/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (!Auth::attempt($credentials)) {
        return response()->json([
            'message' => 'Invalid credentials',
        ], 401);
    }

    $token = $request->user()->createToken('token')->plainTextToken;

    return response()->json([
        'token' => $token,
    ]);
});