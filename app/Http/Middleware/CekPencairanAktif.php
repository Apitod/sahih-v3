<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang modul pencairan.
 *
 * DECISIONS #57 dan #67 menyatakan modul pencairan belum dibangun sampai aturan
 * bonus final. Kodenya sudah ada dan teruji, jadi gerbang ini yang menahan akses
 * alih-alih menghapus route: 404, seolah halaman tidak pernah ada.
 *
 * Nyalakan dengan SAHIH_PENCAIRAN_AKTIF=true di .env setelah Owner mengonfirmasi
 * aturan pencairan (mis. bonus dapat ditarik 1 hari setelah didapat).
 */
class CekPencairanAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('sahih.pencairan_aktif'), 404);

        return $next($request);
    }
}
