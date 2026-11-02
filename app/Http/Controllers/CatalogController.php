<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\Catalog\ProductCatalog;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(private ProductCatalog $catalog) {}

    /** Tüm ürünler ve arama sonuçları. */
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $products = $this->catalog->paginate(null, $filters);
        $facets = $this->catalog->facets(null);

        $seo = (new Seo(title: filled($filters['q']) ? "\"{$filters['q']}\" arama sonuçları" : 'Ürünler'))
            ->crumb('Ana Sayfa', route('home'))
            ->crumb('Ürünler');
        // Arama ve filtre kombinasyonları dizine eklenmesin; ana liste eklenir.
        $seo->noindex = $this->isFiltered($filters);

        return view('catalog.index', compact('products', 'facets', 'filters', 'seo'));
    }

    public function category(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        $filters = $this->filters($request);
        $products = $this->catalog->paginate($category, $filters);
        $facets = $this->catalog->facets($category);

        $seo = Seo::for($category, $category->image)->crumb('Ana Sayfa', route('home'))->crumb('Ürünler', route('products.index'));

        foreach ($category->ancestorsAndSelf() as $item) {
            $seo->crumb($item->name, $item->is($category) ? null : route('categories.show', $item));
        }

        // Açıklama girilmemiş kategoriler için anlamlı bir meta açıklama üret.
        $seo->description ??= "{$category->name}: {$products->total()} ürün. Ürün kodu, ebat ve teknik bilgileri inceleyin, teklif isteyin.";
        $seo->noindex = $seo->noindex || $this->isFiltered($filters);

        return view('catalog.category', compact('category', 'products', 'facets', 'filters', 'seo'));
    }

    private function filters(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));

        return [
            'q' => mb_substr($q, 0, 100),
            'marka' => (array) $request->query('marka', []),
            'ozellik' => array_filter((array) $request->query('ozellik', []), 'is_array'),
            'sirala' => array_key_exists($request->query('sirala'), ProductCatalog::SORTS) ? $request->query('sirala') : 'onerilen',
        ];
    }

    private function isFiltered(array $filters): bool
    {
        return filled($filters['q']) || $filters['marka'] !== [] || $filters['ozellik'] !== [] || $filters['sirala'] !== 'onerilen';
    }
}
