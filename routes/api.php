<?php

use Illuminate\Support\Facades\Route;

// Import API Auth Controllers
use App\Http\Controllers\API\V1\APIAuth\AuthController;
use App\Http\Controllers\API\V1\APIAuth\PasswordResetController;
use App\Http\Controllers\API\V1\APIAuth\EmailVerificationController;

// Import API Product Controllers
use App\Http\Controllers\API\V1\Products\ProductApiController;

// Import API Category Controllers
use App\Http\Controllers\API\V1\Categories\CategoryApiController;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('spa')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register');        
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

        //Reset password feature
        Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.forgot');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.reset');

        // Product endpoints
        Route::get('/products', [ProductApiController::class, 'index'])->name('products.index');
        Route::get('/products/{product:slug}', [ProductApiController::class, 'show'])->name('products.show');

        // Category endpoint
        Route::get('/categories', [CategoryApiController::class, 'index'])->name('categories.index');
        Route::get('/categories/{category:slug}', [CategoryApiController::class, 'show'])->name('categories.show');

        Route::middleware('auth:sanctum')->group(function () {
            //Email verification feature
            Route::get('/email/verification-status', [EmailVerificationController::class, 'status'])->name('verification.status');
            Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
            Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
    });
});
