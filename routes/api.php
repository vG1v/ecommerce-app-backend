<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public API routes
Route::get('/products', function () {
    return response()->json(['message' => 'Products list']);
});

// Auth API routes
Route::post('/register', [RegisteredUserController::class, 'storeApi']);
Route::post('/login', [AuthenticatedSessionController::class, 'apiLogin']); // Add this line

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function () {
        return request()->user();
    });

    // User profile routes
    Route::put('/profile', [ProfileController::class, 'apiUpdate']);
    Route::delete('/profile', [ProfileController::class, 'apiDestroy']);
    Route::post('/logout', [AuthenticatedSessionController::class, 'apiLogout']);
});