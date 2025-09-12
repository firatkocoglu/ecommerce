<?php

use App\Http\Controllers\API\V1\Admin\AdminAuth;
// Import Admin Controller
use App\Http\Controllers\API\V1\APIAuth\AuthController;
// Import API Auth Controllers
use App\Http\Controllers\API\V1\APIAuth\EmailVerificationController;
use App\Http\Controllers\API\V1\APIAuth\PasswordResetController;
use App\Http\Controllers\API\V1\Categories\CategoryAPIController;
// Import API Product Controllers
use App\Http\Controllers\API\V1\Products\ProductApiController;
// Import API Category Controllers
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    /* **
    Admin-only routes
    ** */

    Route::post('/admin/login', [AdminAuth::class, 'login'])->middleware('throttle:10,1')->name('admin.login');

    Route::middleware(['auth:sanctum', 'role:admin,admin', 'throttle:20,1'])->group(function () {
        Route::get('/admin/me', [AdminAuth::class, 'me'])->name('admin.me');
        Route::post('/admin/logout', [AdminAuth::class, 'logout'])->name('admin.logout');
        Route::post('/admin/logout-all', [AdminAuth::class, 'logoutAll'])->name('admin.logout.all');

        Route::post('/categories', [CategoryAPIController::class, 'store'])->name('categories.store');
        Route::match(['put', 'patch'], '/categories/{id}', [CategoryAPIController::class, 'update'])->whereNumber('id')->name('categories.update');
        Route::delete('/categories/{id}', [CategoryAPIController::class, 'destroy'])->whereNumber('id')->name('categories.destroy');
    });

    /* **
    Public routes
    ** */
    // Product endpoints
    Route::get('/products', [ProductApiController::class, 'index'])->name('products.index');
    Route::get('/products/{id}', [ProductApiController::class, 'show'])->name('products.show');

    // Category endpoint
    Route::get('/categories', [CategoryAPIController::class, 'index'])->name('categories.index');
    Route::get('/categories/{id}', [CategoryAPIController::class, 'show'])
        ->whereNumber('id')->name('categories.show');
    Route::get('/categories/tree', [CategoryAPIController::class, 'tree'])->name('categories.tree');

    /* **
    SPA routes
    ** */
    Route::middleware('spa')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

        // Reset password feature
        Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.forgot');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.reset');

        Route::middleware('auth:sanctum')->group(function () {
            // Email verification feature
            Route::get('/email/verification-status', [EmailVerificationController::class, 'status'])->name('verification.status');
            Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
            Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        });
    });
});
