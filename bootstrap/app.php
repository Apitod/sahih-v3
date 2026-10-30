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
        //
    })->create();
