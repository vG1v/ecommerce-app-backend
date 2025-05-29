<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminVendorController;
use Illuminate\Support\Facades\Route;


// User Routes
// Public API routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/search', [ProductController::class, 'search']);    
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/{id}', [ProductController::class, 'show']); 
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
    Route::get('/orders/recent', [OrderController::class, 'getRecentOrders']); // ✅ Move before {id} route
    Route::get('/orders/stats', [OrderController::class, 'getOrderStats']);    // ✅ Move before {id} route
    Route::get('/orders/{id}', [OrderController::class, 'show']);             // Now comes after specific routes
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    
    // For users to update their own orders (limited functionality)
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
});

// Admin API routes
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthenticatedSessionController::class, 'login']);
    
    // Protected admin routes - require authentication and admin role
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        // Auth routes
        Route::post('/logout', [AdminAuthenticatedSessionController::class, 'logout']);
        Route::get('/user', [AdminAuthenticatedSessionController::class, 'user']);
        Route::get('/check-auth', [AdminAuthenticatedSessionController::class, 'checkAuth']);
        Route::post('/register', [AdminAuthenticatedSessionController::class, 'register']); 
        
        // Dashboard
        Route::get('/dashboard/stats', [AdminController::class, 'dashboardStats']);
        Route::get('/dashboard/recent-orders', [AdminController::class, 'recentOrders']);
        Route::get('/dashboard/recent-users', [AdminController::class, 'recentUsers']);
        Route::get('/dashboard/sales-chart', [AdminController::class, 'salesChart']);
        
        // User management
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{id}', [AdminUserController::class, 'show']);
        Route::put('/users/{id}', [AdminUserController::class, 'update']);
        Route::put('/users/{id}/status', [AdminUserController::class, 'updateStatus']);
        Route::post('/users/{id}/role', [AdminUserController::class, 'assignRole']);
        Route::delete('/users/{id}/role/{role}', [AdminUserController::class, 'removeRole']);
        
        // Order management
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
        Route::put('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
        
        // Product management
        Route::get('/products', [AdminProductController::class, 'index']);
        Route::get('/products/{id}', [AdminProductController::class, 'show']);
        Route::post('/products', [AdminProductController::class, 'store']);
        Route::put('/products/{id}', [AdminProductController::class, 'update']);
        Route::delete('/products/{id}', [AdminProductController::class, 'destroy']);
        Route::put('/products/{id}/status', [AdminProductController::class, 'updateStatus']);
        Route::put('/products/{id}/featured', [AdminProductController::class, 'toggleFeatured']);
        
        // Vendor management
        Route::get('/vendors', [AdminVendorController::class, 'index']);
        Route::get('/vendors/{id}', [AdminVendorController::class, 'show']);
        Route::put('/vendors/{id}/status', [AdminVendorController::class, 'updateStatus']);
        Route::get('/vendors/{id}/products', [AdminVendorController::class, 'products']);
    });
});

// In routes/api.php
Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

// Add this route to display all products
