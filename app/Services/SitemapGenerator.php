<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Support\PublicCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Yayındaki sayfa, kategori, marka ve ürünlerden XML sitemap üretir.
 * Sonuç önbelleklenir; içerik kaydedilince PublicCache::flush() ile kendiliğinden yenilenir.
 * noindex işaretli veya başka adrese canonical verilmiş kayıtlar eklenmez.
 */
class SitemapGenerator
{
    public const GENERATED_AT_KEY = 'sitemap.generated_at';

    public function xml(): string
    {
        return Cache::remember(PublicCache::SITEMAP_KEY, now()->addDay(), function () {
            Cache::forever(self::GENERATED_AT_KEY, now()->timestamp);

            return $this->render($this->urls());
        });
    }

    /** Önbelleği temizleyip hemen yeniden üretir. */
    public function regenerate(): string
    {
        Cache::forget(PublicCache::SITEMAP_KEY);

        return $this->xml();
    }

    public function generatedAt(): ?int
    {
        return Cache::get(self::GENERATED_AT_KEY);
    }

    /** @return list<array{type: string, loc: string, lastmod?: mixed, priority: string, image?: ?string}> */
    public function urls(): array
    {
        $urls = [
            ['type' => 'Sistem sayfası', 'loc' => route('home'), 'priority' => '1.0'],
            ['type' => 'Sistem sayfası', 'loc' => route('products.index'), 'priority' => '0.9'],
            ['type' => 'Sistem sayfası', 'loc' => route('brands.index'), 'priority' => '0.6'],
            ['type' => 'Sistem sayfası', 'loc' => route('catalogs.index'), 'priority' => '0.6'],
            ['type' => 'Sistem sayfası', 'loc' => route('quote.create'), 'priority' => '0.5'],
            ['type' => 'Sistem sayfası', 'loc' => route('contact'), 'priority' => '0.5'],
        ];

        $indexable = fn ($query) => $query->where('noindex', false)->where(fn ($q) => $q->whereNull('canonical_url')->orWhere('canonical_url', ''));

        Page::query()->published()->corporate()->tap($indexable)
            ->where('slug', '!=', 'iletisim')
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Page $page) use (&$urls) {
                $urls[] = ['type' => 'Kurumsal sayfa', 'loc' => route('pages.show', $page), 'lastmod' => $page->updated_at, 'priority' => '0.5'];
            });

        Category::query()->active()->tap($indexable)->get(['id', 'slug', 'updated_at'])
            ->each(function (Category $category) use (&$urls) {
                $urls[] = ['type' => 'Kategori', 'loc' => route('categories.show', $category), 'lastmod' => $category->updated_at, 'priority' => '0.8'];
            });

        Brand::query()->active()->tap($indexable)->get(['id', 'slug', 'updated_at'])
            ->each(function (Brand $brand) use (&$urls) {
                $urls[] = ['type' => 'Marka', 'loc' => route('brands.show', $brand), 'lastmod' => $brand->updated_at, 'priority' => '0.6'];
            });

        Product::query()->active()->tap($indexable)->with('coverImage')
            ->select(['id', 'slug', 'name', 'updated_at'])
            ->chunkById(1000, function ($products) use (&$urls) {
                foreach ($products as $product) {
                    $urls[] = [
                        'type' => 'Ürün',
                        'loc' => route('products.show', $product),
                        'lastmod' => $product->updated_at,
                        'priority' => '0.7',
                        'image' => $product->coverImage ? url(Storage::disk('public')->url($product->coverImage->path)) : null,
                    ];
                }
            });

        return array_slice($urls, 0, 50000);
    }

    private function render(array $urls): string
    {
        $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";

        foreach ($urls as $url) {
            $out .= '  <url><loc>'.e($url['loc']).'</loc>';
            $out .= isset($url['lastmod']) && $url['lastmod'] ? '<lastmod>'.$url['lastmod']->toAtomString().'</lastmod>' : '';
            $out .= '<priority>'.$url['priority'].'</priority>';
            $out .= ($url['image'] ?? null) ? '<image:image><image:loc>'.e($url['image']).'</image:loc></image:image>' : '';
            $out .= "</url>\n";
        }

        return $out.'</urlset>'."\n";
    }
}
