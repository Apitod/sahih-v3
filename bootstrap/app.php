<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tamu yang membuka halaman admin dikirim ke login admin, bukan login anggota.
        $middleware->redirectGuestsTo(fn (\Illuminate\Http\Request $request) => $request->is('admin*')
            ? route('admin.login')
            : route('login'));

        // Pengguna terautentikasi yang membuka halaman login dikirim ke beranda masing-masing.
        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $request) => $request->is('admin*')
            ? route('admin.beranda')
            : route('beranda'));

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->alias([
            'active.member' => \App\Http\Middleware\EnsureActiveMember::class,
            'active.admin' => \App\Http\Middleware\EnsureActiveAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Formulir pendaftaran yang terkirim dua kali (ketukan ganda di HP, atau
        // sesi kedaluwarsa) jangan berakhir di halaman mati "419 Page Expired".
        // Pendaftaran pertama sudah me-regenerate sesi, jadi token di halaman lama
        // otomatis tidak sah. Kirim balik ke form, atau ke beranda kalau ternyata
        // pendaftaran pertama sudah berhasil.
        // Catatan: TokenMismatchException sudah diubah jadi HttpException(419) oleh
        // Handler::prepareException(), jadi yang ditangkap adalah status 419.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419 || ! $request->routeIs('daftar.store')) {
                return null;
            }

            if (\Illuminate\Support\Facades\Auth::guard('web')->check()) {
                return redirect()->route('beranda')->with('sukses', 'Pendaftaran Anda sudah berhasil.');
            }

            return redirect()->route('daftar')
                ->withErrors(['code' => 'Sesi halaman kedaluwarsa. Isi ulang formulir lalu kirim sekali saja.']);
        });
    })->create();
