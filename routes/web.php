<?php

use App\Http\Controllers\API\V1\Admin\AdminAuth;
use App\Http\Controllers\ProfileController;
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

// Session based admin login routes
Route::get('/admin/login', [AdminAuth::class, 'show'])->middleware('throttle:10,1')->name('admin.login');
Route::post('/admin/login', [AdminAuth::class, 'login'])->middleware('throttle:10,1')->name('admin.login.submit');

require __DIR__.'/auth.php';
