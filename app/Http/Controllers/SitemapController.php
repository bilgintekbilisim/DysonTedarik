<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Support\PublicCache;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Yayındaki sayfa, kategori, marka ve ürünlerden XML sitemap üretir.
 * Sonuç önbelleklenir; içerik kaydedilince PublicCache::flush() ile kendiliğinden yenilenir.
 * noindex işaretli veya başka adrese canonical verilmiş kayıtlar eklenmez.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember(PublicCache::SITEMAP_KEY, now()->addDay(), fn () => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function build(): string
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('products.index'), 'priority' => '0.9'],
            ['loc' => route('brands.index'), 'priority' => '0.6'],
            ['loc' => route('catalogs.index'), 'priority' => '0.6'],
            ['loc' => route('quote.create'), 'priority' => '0.5'],
            ['loc' => route('contact'), 'priority' => '0.5'],
        ];

        $indexable = fn ($query) => $query->where('noindex', false)->where(fn ($q) => $q->whereNull('canonical_url')->orWhere('canonical_url', ''));

        Page::query()->published()->corporate()->tap($indexable)
            ->where('slug', '!=', 'iletisim')
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Page $page) use (&$urls) {
                $urls[] = ['loc' => route('pages.show', $page), 'lastmod' => $page->updated_at, 'priority' => '0.5'];
            });

        Category::query()->active()->tap($indexable)->get(['id', 'slug', 'updated_at'])
            ->each(function (Category $category) use (&$urls) {
                $urls[] = ['loc' => route('categories.show', $category), 'lastmod' => $category->updated_at, 'priority' => '0.8'];
            });

        Brand::query()->active()->tap($indexable)->get(['id', 'slug', 'updated_at'])
            ->each(function (Brand $brand) use (&$urls) {
                $urls[] = ['loc' => route('brands.show', $brand), 'lastmod' => $brand->updated_at, 'priority' => '0.6'];
            });

        Product::query()->active()->tap($indexable)->with('coverImage')
            ->select(['id', 'slug', 'name', 'updated_at'])
            ->chunkById(1000, function ($products) use (&$urls) {
                foreach ($products as $product) {
                    $urls[] = [
                        'loc' => route('products.show', $product),
                        'lastmod' => $product->updated_at,
                        'priority' => '0.7',
                        'image' => $product->coverImage ? url(Storage::disk('public')->url($product->coverImage->path)) : null,
                        'title' => $product->name,
                    ];
                }
            });

        $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";

        foreach (array_slice($urls, 0, 50000) as $url) {
            $out .= '  <url><loc>'.e($url['loc']).'</loc>';
            $out .= isset($url['lastmod']) && $url['lastmod'] ? '<lastmod>'.$url['lastmod']->toAtomString().'</lastmod>' : '';
            $out .= '<priority>'.$url['priority'].'</priority>';
            $out .= ($url['image'] ?? null) ? '<image:image><image:loc>'.e($url['image']).'</image:loc></image:image>' : '';
            $out .= "</url>\n";
        }

        return $out.'</urlset>'."\n";
    }
}
