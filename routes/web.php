<?php

use App\Http\Controllers\Auth\AdminAuth;
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

Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');

// Session based admin auth routes
Route::get('/admin/login', [AdminAuth::class, 'show'])->middleware('throttle:10,1')->name('admin.login');
Route::post('/admin/login', [AdminAuth::class, 'login'])->middleware('throttle:10,1')->name('admin.login.submit');

Route::middleware(['auth:admin'])->group(function () {
    Route::get('/admin/me', [AdminAuth::class, 'me'])->name('admin.me');
    Route::post('/admin/logout', [AdminAuth::class, 'logout'])->name('admin.logout');
    Route::post('/admin/logout-all', [AdminAuth::class, 'logoutAll'])->name('admin.logout.all');
});
