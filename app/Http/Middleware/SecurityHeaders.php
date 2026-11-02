<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tarayıcı güvenlik başlıkları. CSP bilerek eklenmedi: Alpine.js ve Livewire (Filament)
 * satır içi ifade değerlendirmesi kullandığı için katı bir CSP paneli bozar.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->remove('X-Powered-By');

        if ($request->isSecure() && app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        // Panel ve form sonuç sayfaları arama motorlarında görünmesin.
        if ($request->is('admin', 'admin/*', 'livewire/*')) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
