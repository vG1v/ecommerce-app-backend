<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;


// User Routes
// Public API routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/{product}/related', [ProductController::class, 'related']);

// Auth API routes
Route::post('/register', [RegisteredUserController::class, 'storeApi']);
Route::post('/login', [AuthenticatedSessionController::class, 'apiLogin']); 

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function () {
        return request()->user();
    });

    // User profile routes
    Route::put('/profile', [ProfileController::class, 'apiUpdate']);
    Route::delete('/profile', [ProfileController::class, 'apiDestroy']);
    Route::post('/logout', [AuthenticatedSessionController::class, 'apiLogout']);
    
    // Cart routes
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'addItem']);
    Route::put('/cart/items/{id}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);
    Route::delete('/cart/clear', [CartController::class, 'clear']);
    Route::get('/cart/count', [CartController::class, 'getCount']);
    
    // Wishlist routes
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/add', [WishlistController::class, 'addItem']);
    Route::delete('/wishlist/items/{id}', [WishlistController::class, 'removeItem']);
    Route::delete('/wishlist/clear', [WishlistController::class, 'clear']);
    Route::get('/wishlist/check/{productId}', [WishlistController::class, 'checkItem']);
    Route::get('/wishlist/count', [WishlistController::class, 'getCount']);
    
    // Order routes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    Route::get('/orders/recent', [OrderController::class, 'getRecentOrders']);
    Route::get('/orders/stats', [OrderController::class, 'getOrderStats']);
});

// Admin API routes
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthenticatedSessionController::class, 'login']);
    
    // Protected admin routes - require authentication and admin role
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/logout', [AdminAuthenticatedSessionController::class, 'logout']);
        Route::get('/user', [AdminAuthenticatedSessionController::class, 'user']);
        Route::get('/check-auth', [AdminAuthenticatedSessionController::class, 'checkAuth']);
        Route::post('/register', [AdminAuthenticatedSessionController::class, 'register']); 
    });
});

// In routes/api.php
Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});