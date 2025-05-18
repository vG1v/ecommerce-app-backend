<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// API Routes
// Route::prefix('api')->group(function () {
//     // Public API routes
//     Route::get('/products', function () {
//         return response()->json(['message' => 'Products list']);
//     });
    
//     // Auth API routes
//     Route::post('/register', [RegisteredUserController::class, 'storeApi']);
    
//     // Protected API routes
//     Route::middleware('auth:sanctum')->group(function () {
//         Route::get('/user', function () {
//             return request()->user();
//         });
//     });
// });

require __DIR__.'/auth.php';
