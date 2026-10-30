<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\MemberLoginController;
use App\Http\Controllers\MemberDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth('web')->check()
    ? redirect()->route('beranda')
    : redirect()->route('login'));

// Anggota
Route::get('/masuk', [MemberLoginController::class, 'create'])->name('login');
Route::post('/masuk', [MemberLoginController::class, 'store'])->name('login.store');
Route::post('/keluar', [MemberLoginController::class, 'destroy'])->name('logout');

Route::middleware('auth:web')->group(function () {
    Route::get('/beranda', [MemberDashboardController::class, 'index'])->name('beranda');
});

// Admin
Route::get('/admin/masuk', [AdminLoginController::class, 'create'])->name('admin.login');
Route::post('/admin/masuk', [AdminLoginController::class, 'store'])->name('admin.login.store');
Route::post('/admin/keluar', [AdminLoginController::class, 'destroy'])->name('admin.logout');

Route::middleware('auth:admin')->prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.beranda');
});
