<?php

use App\Http\Controllers\Admin\PinController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\MemberLoginController;
use App\Http\Controllers\Member\PinRedemptionController;
use App\Http\Controllers\MemberDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth('web')->check()
    ? redirect()->route('beranda')
    : redirect()->route('login'));

// Anggota
Route::get('/masuk', [MemberLoginController::class, 'create'])->name('login');
Route::post('/masuk', [MemberLoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
Route::post('/keluar', [MemberLoginController::class, 'destroy'])->name('logout');

Route::middleware('auth:web')->group(function () {
    Route::get('/beranda', [MemberDashboardController::class, 'index'])->name('beranda');

    Route::get('/tukar', [PinRedemptionController::class, 'create'])->name('tukar');
    Route::post('/tukar', [PinRedemptionController::class, 'store'])->middleware('throttle:10,1')->name('tukar.store');
});

// Admin
Route::get('/admin/masuk', [AdminLoginController::class, 'create'])->name('admin.login');
Route::post('/admin/masuk', [AdminLoginController::class, 'store'])->middleware('throttle:10,1')->name('admin.login.store');
Route::post('/admin/keluar', [AdminLoginController::class, 'destroy'])->name('admin.logout');

Route::middleware('auth:admin')->prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.beranda');

    Route::get('/pin', [PinController::class, 'index'])->name('admin.pin.index');
    Route::get('/pin/baru', [PinController::class, 'create'])->name('admin.pin.create');
    Route::post('/pin', [PinController::class, 'store'])->name('admin.pin.store');
    Route::post('/pin/{pin}/batalkan', [PinController::class, 'void'])->name('admin.pin.void');
});
