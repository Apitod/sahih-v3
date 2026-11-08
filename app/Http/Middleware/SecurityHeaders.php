<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Satu host saja: buang "www.". Kalau dua host hidup, cookie sesi www.* dan
        // domain utama terpisah, lalu form yang action-nya absolut (route()) terkirim
        // ke host lain tanpa cookie sesi → 419 Page Expired saat daftar.
        if (str_starts_with($request->getHost(), 'www.')) {
            return redirect()->away(preg_replace('#://www\.#', '://', $request->fullUrl()), 301);
        }

        $response = $next($request);

        // ponytail: minimal security headers, CSP omitted to avoid breaking inline assets, HSTS deferred until HTTPS.
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');

        return $response;
    }
}
