<?php

namespace App\Services;

use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * 404 olan bir adres için tanımlı 301/302 yönlendirmeyi bulur.
 * Yalnızca sayfa bulunamadığında çalıştığı için yayındaki bir sayfayı asla gölgelemez.
 */
class RedirectResolver
{
    public const CACHE_KEY = 'redirects.map';

    public function respond(Request $request): ?RedirectResponse
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true) || $request->is('admin', 'admin/*', 'livewire/*')) {
            return null;
        }

        $path = Redirect::normalizePath('/'.$request->path());
        $target = $this->map()[$path] ?? null;

        if (! $target) {
            return null;
        }

        [$to, $status] = $target;
        $destination = str_starts_with($to, '/') ? url($to) : $to;

        // Döngü koruması: hedef, istenen adresin kendisi olamaz.
        if (Redirect::normalizePath($destination) === $path && ! str_starts_with($to, 'http')) {
            return null;
        }

        Redirect::query()->where('from_path', $path)->incrementEach(['hits' => 1], ['last_hit_at' => now()]);

        return redirect()->to($destination, $status);
    }

    /** @return array<string, array{0: string, 1: int}> */
    public function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => Redirect::query()
            ->where('is_active', true)
            ->get(['from_path', 'to_url', 'status_code'])
            ->mapWithKeys(fn (Redirect $r) => [$r->from_path => [$r->to_url, (int) $r->status_code]])
            ->all());
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
