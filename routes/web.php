<?php

use App\Http\Controllers\Admin\AnggotaController;
use App\Http\Middleware\CekPencairanAktif;
use App\Http\Controllers\Admin\FinansialController;
use App\Http\Controllers\Admin\HadiahAdminController;
use App\Http\Controllers\Admin\KlaimAdminController;
use App\Http\Controllers\Admin\PencairanAdminController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PinController;
use App\Http\Controllers\Admin\TransaksiController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\MemberLoginController;
use App\Http\Controllers\Member\HadiahController;
use App\Http\Controllers\Member\KlaimHadiahController;
use App\Http\Controllers\Member\PinRedemptionController;
use App\Http\Controllers\Member\PohonController;
use App\Http\Controllers\Member\ProfilController;
use App\Http\Controllers\Member\RiwayatController;
use App\Http\Controllers\Member\SaldoController;
use App\Http\Controllers\MemberDashboardController;
use App\Http\Controllers\RegistrasiController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth('web')->check()
    ? redirect()->route('beranda')
    : redirect()->route('login'));

// Anggota
Route::middleware('guest:web')->group(function () {
    Route::get('/masuk', [MemberLoginController::class, 'create'])->name('login');
    Route::post('/masuk', [MemberLoginController::class, 'store'])->middleware('throttle:30,1,login-anggota')->name('login.store');
    Route::get('/daftar', [RegistrasiController::class, 'create'])->name('daftar');
    Route::post('/daftar', [RegistrasiController::class, 'store'])->middleware('throttle:30,1,daftar-anggota')->name('daftar.store');
});
Route::post('/keluar', [MemberLoginController::class, 'destroy'])->name('logout');

Route::middleware(['auth:web', 'active.member'])->group(function () {
    Route::get('/beranda', [MemberDashboardController::class, 'index'])->name('beranda');
    Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat');
    Route::get('/pohon', [PohonController::class, 'index'])->name('pohon');
    Route::get('/hadiah', [HadiahController::class, 'index'])->name('hadiah');
    Route::get('/panduan', fn() => view('member.panduan'))->name('panduan');
    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::post('/profil', [ProfilController::class, 'update'])->name('profil.update');

    Route::get('/saldo', [SaldoController::class, 'index'])->middleware(CekPencairanAktif::class)->name('saldo');
    Route::get('/tarik', [SaldoController::class, 'create'])->middleware(CekPencairanAktif::class)->name('tarik');
    Route::post('/tarik', [SaldoController::class, 'store'])->middleware(CekPencairanAktif::class)->name('tarik.store');

    Route::get('/tukar', [PinRedemptionController::class, 'create'])->name('tukar');
    Route::post('/hadiah/klaim', [KlaimHadiahController::class, 'store'])->name('hadiah.klaim');

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
    Route::get('/panduan', fn() => view('admin.panduan'))->name('admin.panduan');

    Route::get('/anggota', [AnggotaController::class, 'index'])->name('admin.anggota.index');
    Route::get('/anggota/{anggota}', [AnggotaController::class, 'show'])->name('admin.anggota.show');
    Route::get('/anggota/{anggota}/edit', [AnggotaController::class, 'edit'])->name('admin.anggota.edit');
    Route::post('/anggota/{anggota}', [AnggotaController::class, 'update'])->name('admin.anggota.update');
    Route::post('/anggota/{anggota}/reset-password', [AnggotaController::class, 'resetPassword'])->name('admin.anggota.reset-password');

    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('admin.transaksi.index');
    Route::get('/transaksi/{transaksi}', [TransaksiController::class, 'show'])->name('admin.transaksi.show');

    Route::get('/hadiah', [HadiahAdminController::class, 'index'])->name('admin.hadiah.index');
    Route::get('/hadiah/{reward}/edit', [HadiahAdminController::class, 'edit'])->name('admin.hadiah.edit');
    Route::post('/hadiah/{reward}', [HadiahAdminController::class, 'update'])->name('admin.hadiah.update');
    Route::post('/hadiah', [HadiahAdminController::class, 'store'])->name('admin.hadiah.store');

    Route::get('/pin', [PinController::class, 'index'])->name('admin.pin.index');
    Route::get('/pin/baru', [PinController::class, 'create'])->name('admin.pin.create');
    Route::post('/pin', [PinController::class, 'store'])->name('admin.pin.store');
    Route::post('/pin/{pin}/batalkan', [PinController::class, 'void'])->name('admin.pin.void');

    // Pengaturan harga & ambang — hanya Superadmin (lihat PengaturanController).
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('admin.pengaturan');
    Route::post('/pengaturan', [PengaturanController::class, 'simpan'])->name('admin.pengaturan.simpan');
    Route::get('/klaim', [KlaimAdminController::class, 'index'])->name('admin.klaim');
    Route::post('/klaim/{klaim}/serahkan', [KlaimAdminController::class, 'serahkan'])->name('admin.klaim.serahkan');

    Route::get('/finansial', [FinansialController::class, 'index'])->name('admin.finansial');

    Route::get('/pencairan', [PencairanAdminController::class, 'index'])->middleware(CekPencairanAktif::class)->name('admin.pencairan');
    Route::post('/pencairan/{withdrawal}/proses', [PencairanAdminController::class, 'proses'])->middleware(CekPencairanAktif::class)->name('admin.pencairan.proses');
});
