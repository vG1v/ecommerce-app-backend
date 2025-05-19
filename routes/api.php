<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


// User Routes
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


// Admin API routes
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthenticatedSessionController::class, 'login']);
    
    // Protected admin routes - require authentication and admin role
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/logout', [AdminAuthenticatedSessionController::class, 'logout']);
        Route::get('/user', [AdminAuthenticatedSessionController::class, 'user']);
        Route::get('/check-auth', [AdminAuthenticatedSessionController::class, 'checkAuth']);
        Route::post('/register', [AdminAuthenticatedSessionController::class, 'register']); // Add this line
    });
});