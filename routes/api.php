<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

// Public API routes
Route::get('/products', function () {
    return response()->json(['message' => 'Products list']);
});

// Auth API routes
Route::post('/register', [RegisteredUserController::class, 'storeApi']);

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function () {
        return request()->user();
    });
});