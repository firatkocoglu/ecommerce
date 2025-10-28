<?php

use App\Http\Controllers\API\V1\Addresses\AddressController;
use App\Http\Controllers\API\V1\Carts\CartController;
use App\Http\Controllers\API\V1\Orders\OrderController;
use App\Http\Controllers\API\V1\Payments\PaymentController;
use App\Http\Controllers\Auth\APIAuth\AuthController;
use App\Http\Controllers\Auth\APIAuth\EmailVerificationController;
use App\Http\Controllers\Auth\APIAuth\PasswordResetController;
use App\Http\Middleware\MergeGuestCart;
use Illuminate\Support\Facades\Route;

// SPA API routes
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware(['web'])->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

        // Reset password feature
        Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.forgot');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.reset');

        Route::middleware(['web', 'auth:web', MergeGuestCart::class])->group(function () {
            // Email verification feature
            Route::get('/email/verification-status', [EmailVerificationController::class, 'status'])->name('verification.status');
            Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
            Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

            // Authenticated user endpoints
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

            // Address endpoints for authenticated users
            Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
            Route::get('/addresses/{addressId}', [AddressController::class, 'show'])->name('addresses.show');
            Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
            Route::match(['put', 'patch'], '/addresses/{addressId}', [AddressController::class, 'update'])->name('addresses.update');
            Route::delete('/addresses/clear', [AddressController::class, 'clearAll'])->name('addresses.clear');
            Route::delete('/addresses/{addressId}', [AddressController::class, 'destroy'])->name('addresses.destroy');
            Route::patch('/addresses/{addressId}/set-default', [AddressController::class, 'toggleDefault'])->name('addresses.setDefault');

            // Cart endpoints for authenticated users
            Route::get('/cart', [CartController::class, 'show'])->name('cart.user.show');
            Route::post('/cart', [CartController::class, 'store'])->name('cart.user.store');
            Route::post('/cart/add-item', [CartController::class, 'addItem'])->name('cart.user.addItem');
            Route::delete('/cart/remove-item', [CartController::class, 'removeItem'])->name('cart.user.removeItem');
            Route::delete('/cart/clear', [CartController::class, 'clearCart'])->name('cart.user.clear');
            Route::match(['put', 'patch'], '/cart/increase-quantity', [CartController::class, 'increaseItemQuantity'])->name('cart.user.update');
            Route::match(['put', 'patch'], '/cart/decrease-quantity', [CartController::class, 'decreaseItemQuantity'])->name('cart.user.update');

            // Order endpoints for authenticated users
            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{orderId}', [OrderController::class, 'show'])->name('orders.show');
            Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
            Route::patch('/orders/{orderId}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
            // Payment endpoints for authenticated users
            Route::post('/payments/create-intent', [PaymentController::class, 'createPaymentIntent'])->name('payments.createIntent');
        });
    });
});
