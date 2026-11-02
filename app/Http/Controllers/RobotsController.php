<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Dinamik robots.txt. Production dışındaki ortamlar (test/staging) tamamen kapatılır;
 * böylece test sunucusu yanlışlıkla Google dizinine girmez.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = app()->isProduction()
            ? [
                'User-agent: *',
                'Disallow: /admin',
                'Disallow: /livewire',
                'Disallow: /arama/',
                '',
                'Sitemap: '.route('sitemap'),
            ]
            : [
                'User-agent: *',
                'Disallow: /',
            ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
