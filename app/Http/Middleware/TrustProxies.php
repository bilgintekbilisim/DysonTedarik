<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;

/**
 * Güvenilen proxy listesi config('app.trusted_proxies') değerinden okunur (TRUSTED_PROXIES).
 * bootstrap/app.php içinde env() config önbelleğiyle çalışmadığı için ayrı sınıf kullanılır.
 */
class TrustProxies extends Middleware
{
    protected function proxies()
    {
        $proxies = config('app.trusted_proxies');

        if (blank($proxies)) {
            return parent::proxies();
        }

        return $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies));
    }
}
