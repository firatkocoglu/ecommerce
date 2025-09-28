<?php

use App\Http\Controllers\API\V1\Admin\AdminAuth;
// Import Admin Controller
use App\Http\Controllers\API\V1\APIAuth\AuthController;
// Import API Auth Controllers
use App\Http\Controllers\API\V1\APIAuth\EmailVerificationController;
use App\Http\Controllers\API\V1\APIAuth\PasswordResetController;
use App\Http\Controllers\API\V1\Categories\CategoryAPIController;
// Import API Product Controllers
use App\Http\Controllers\API\V1\ProductImages\ProductImageApiController;
use App\Http\Controllers\API\V1\Products\ProductApiController;
// Import API Product Variant Controllers
use App\Http\Controllers\API\V1\ProductVariants\ProductVariantApiController;
// Import API Category Controllers
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Admin login routes with token based auth
    Route::get('/admin/login', [AdminAuth::class, 'show'])->middleware('throttle:10,1')->name('admin.login');
    Route::post('/admin/login', [AdminAuth::class, 'login'])->middleware('throttle:10,1')->name('admin.login.submit');

    /* **
   Admin-only routes
   ** */
    Route::middleware(['auth:sanctum', 'role:admin,admin', 'throttle:20,1'])->group(function () {
        Route::get('/admin/me', [AdminAuth::class, 'me'])->name('admin.me');
        Route::post('/admin/logout', [AdminAuth::class, 'logout'])->name('admin.logout');
        Route::post('/admin/logout-all', [AdminAuth::class, 'logoutAll'])->name('admin.logout.all');

        // Category endpoints for admin
        Route::post('/categories', [CategoryAPIController::class, 'store'])->name('categories.store');
        Route::match(['put', 'patch'], '/categories/{category}', [CategoryAPIController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryAPIController::class, 'destroy'])->name('categories.destroy');

        // Product endpoints for admin
        Route::post('/products', [ProductApiController::class, 'store'])->name('products.store');
        Route::match(['put', 'patch'], '/products/{product}', [ProductApiController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductApiController::class, 'destroy'])->name('products.destroy');

        // Product variant endpoints for admin
        Route::post('/products/{product}/variants', [ProductVariantApiController::class, 'store'])->name('variants.store');
        Route::scopeBindings()->group(function () {
            Route::match(['put', 'patch'], '/products/{product}/variants/{variant}', [ProductVariantApiController::class, 'update'])->name('variants.update');
            Route::delete('/products/{product}/variants/{variant}', [ProductVariantApiController::class, 'destroy'])->name('variants.destroy');
        });

        // Product image endpoints for admin
        Route::scopeBindings()->group(function () {
            Route::post('/products/{product}/images', [ProductImageApiController::class, 'store'])->name('images.store');
            Route::post('/products/{product}/variants/{variant}/images', [ProductImageApiController::class, 'store'])->name('images.store.variant');
            Route::match(['put', 'patch'], '/products/{product}/images/{image}', [ProductImageApiController::class, 'update'])->name('images.update');
            Route::match(['put', 'patch'], '/products/{product}/variants/{variant}/images/{image}', [ProductImageApiController::class, 'updateVariant'])->name('images.update.variant');
            Route::delete('/products/{product}/images/{image}', [ProductImageApiController::class, 'destroy'])->name('images.destroy');
            Route::delete('/products/{product}/variants/{variant}/images/{image}', [ProductImageApiController::class, 'destroyVariant'])->name('images.destroy.variant');
        });
    });

    /* **
    Public routes
    ** */

    // Category endpoint
    Route::get('/categories', [CategoryAPIController::class, 'index'])->name('categories.index');
    Route::get('/categories/{category}', [CategoryAPIController::class, 'show'])->name('categories.show');
    Route::get('/categories/tree', [CategoryAPIController::class, 'tree'])->name('categories.tree');

    // Product endpoints
    Route::get('/products', [ProductApiController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductApiController::class, 'show'])->name('products.show');

    // Variant endpoints
    Route::get('/products/{product}/variants', [ProductVariantApiController::class, 'index'])->name('variants.index');
    Route::scopeBindings()->group(function () {
        Route::get('/products/{product}/variants/{variant}', [ProductVariantApiController::class, 'show'])
            ->name('variants.show');
    });

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
