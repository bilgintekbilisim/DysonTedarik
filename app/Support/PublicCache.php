<?php

namespace App\Support;

use App\Models\Menu;
use Illuminate\Support\Facades\Cache;

/**
 * Ön yüzde önbelleklenen ve içerik değişince yenilenmesi gereken veriler:
 * menüler/mega menü ve sitemap.xml. Sayfa, kategori, marka ve ürün kaydedildiğinde çağrılır.
 */
class PublicCache
{
    public const SITEMAP_KEY = 'sitemap.xml';

    public static function flush(): void
    {
        Menu::flushCache();
        Cache::forget(self::SITEMAP_KEY);
    }
}
