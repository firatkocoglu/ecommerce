<?php

use App\Http\Controllers\API\V1\Categories\CategoryAPIController;
use App\Http\Controllers\API\V1\ProductImages\ProductImageApiController;
use App\Http\Controllers\API\V1\Products\ProductApiController;
use App\Http\Controllers\API\V1\ProductVariants\ProductVariantApiController;
use Illuminate\Support\Facades\Route;

// Admin API routes
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware(['web', 'auth:admin', 'throttle:20,1'])->group(function () {
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
        Route::post('/products/{product}/images', [ProductImageApiController::class, 'store'])->name('images.store');
        Route::match(['put', 'patch'], '/products/{productId}/images/{imageId}', [ProductImageApiController::class, 'update'])->name('images.update');
        Route::match(['put', 'patch'], '/products/{productId}/variants/{variantId}/images/{imageId}', [ProductImageApiController::class, 'updateVariant'])->name('images.update.variant');
        Route::delete('/products/{productId}/images/delete', [ProductImageApiController::class, 'destroy'])->name('images.destroy');
        Route::delete('/products/{productId}/variants/{variantId}/images/delete', [ProductImageApiController::class, 'destroyVariant'])->name('images.destroy.variant');

        Route::scopeBindings()->group(function () {
            Route::post('/products/{product}/variants/{variant}/images', [ProductImageApiController::class, 'store'])->name('images.store.variant');
        });
    });
});
