<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Production'da tüm istekleri APP_URL'deki tek adrese 301 ile toplar:
 * http → https ve www / www'suz farkı. Böylece aynı sayfa iki adreste dizine girmez.
 */
class CanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isProduction() || ! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $canonical = parse_url((string) config('app.url'));
        $host = $canonical['host'] ?? null;
        $scheme = $canonical['scheme'] ?? 'https';

        if (! $host) {
            return $next($request);
        }

        if (strcasecmp($request->getHost(), $host) !== 0 || $request->getScheme() !== $scheme) {
            return redirect()->to($scheme.'://'.$host.$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
