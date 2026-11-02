<?php

use App\Http\Controllers\Admin\PengaturanController;
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
Route::middleware('guest:web')->group(function () {
    Route::get('/masuk', [MemberLoginController::class, 'create'])->name('login');
    Route::post('/masuk', [MemberLoginController::class, 'store'])->middleware('throttle:30,1,login-anggota')->name('login.store');
});
Route::post('/keluar', [MemberLoginController::class, 'destroy'])->name('logout');

Route::middleware(['auth:web', 'active.member'])->group(function () {
    Route::get('/beranda', [MemberDashboardController::class, 'index'])->name('beranda');

    Route::get('/tukar', [PinRedemptionController::class, 'create'])->name('tukar');

    // Batas 60/menit per IP, bukan 10: beberapa anggota bisa satu kantor dengan satu IP
    // dan menukar PIN berdekatan. Percobaan menebak kode tetap tidak masuk akal
    // (12 karakter acak), dan pembatasan per akun tetap ada di controller login.
    Route::post('/tukar', [PinRedemptionController::class, 'store'])->middleware('throttle:60,1')->name('tukar.store');
});

// Admin
Route::middleware('guest:admin')->group(function () {
    Route::get('/admin/masuk', [AdminLoginController::class, 'create'])->name('admin.login');
    Route::post('/admin/masuk', [AdminLoginController::class, 'store'])->middleware('throttle:10,1,login-admin')->name('admin.login.store');
});
Route::post('/admin/keluar', [AdminLoginController::class, 'destroy'])->name('admin.logout');

Route::middleware(['auth:admin', 'active.admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.beranda');

    Route::get('/pin', [PinController::class, 'index'])->name('admin.pin.index');
    Route::get('/pin/baru', [PinController::class, 'create'])->name('admin.pin.create');
    Route::post('/pin', [PinController::class, 'store'])->name('admin.pin.store');
    Route::post('/pin/{pin}/batalkan', [PinController::class, 'void'])->name('admin.pin.void');

    // Pengaturan harga & ambang — hanya Superadmin (lihat PengaturanController).
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('admin.pengaturan');
    Route::post('/pengaturan', [PengaturanController::class, 'simpan'])->name('admin.pengaturan.simpan');
});
