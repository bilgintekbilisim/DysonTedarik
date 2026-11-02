<?php

use App\Http\Middleware\CanonicalHost;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrustProxies;
use App\Services\RedirectResolver;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(CanonicalHost::class);
        $middleware->append(SecurityHeaders::class);

        // Cloudflare veya hosting proxy'si arkasındaysa .env'de TRUSTED_PROXIES tanımlanır (ör. "*").
        $middleware->replace(Illuminate\Http\Middleware\TrustProxies::class, TrustProxies::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Bulunamayan adres için panelde tanımlı 301/302 yönlendirme varsa uygula.
        $exceptions->render(fn (NotFoundHttpException $e, Request $request) => app(RedirectResolver::class)->respond($request));
    })->create();
