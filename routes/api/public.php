<?php

use App\Http\Controllers\API\V1\Carts\CartController;
use App\Http\Controllers\API\V1\Categories\CategoryAPIController;
use App\Http\Controllers\API\V1\Products\ProductApiController;
use App\Http\Controllers\API\V1\ProductVariants\ProductVariantApiController;
use Illuminate\Support\Facades\Route;

// Public API routes
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('web')->group(function () {
        // Category endpoint
        Route::get('/categories', [CategoryAPIController::class, 'index'])->name('categories.index');
        Route::get('/categories/{category}', [CategoryAPIController::class, 'show'])->name('categories.show');
        Route::get('/categories/tree', [CategoryAPIController::class, 'tree'])->name('categories.tree');

        // Product endpoints
        Route::get('/products', [ProductApiController::class, 'index'])->name('products.index');
        Route::get('/products/{id}', [ProductApiController::class, 'show'])->name('products.show');

        // Variant endpoints
        Route::get('/products/{productId}/variants', [ProductVariantApiController::class, 'index'])->name('variants.index');
        Route::get('/products/{productId}/variants/{variantId}', [ProductVariantApiController::class, 'show'])
            ->name('variants.show');

        // Guest cart endpoints
        Route::get('guest/cart', [CartController::class, 'show'])->name('cart.show');
        Route::post('guest/cart', [CartController::class, 'store'])->name('cart.store');
        Route::post('guest/cart/add-item', [CartController::class, 'addItem'])->name('cart.addItem');
        Route::delete('guest/cart/remove-item', [CartController::class, 'removeItem'])->name('cart.removeItem');
        Route::delete('guest/cart/clear', [CartController::class, 'clearCart'])->name('cart.clear');
        Route::match(['put', 'patch'], 'guest/cart/increase-quantity', [CartController::class, 'increaseItemQuantity'])->name('cart.update');
        Route::match(['put', 'patch'], 'guest/cart/decrease-quantity', [CartController::class, 'decreaseItemQuantity'])->name('cart.update');
    });
});
