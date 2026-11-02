<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Services\Catalog\ProductCatalog;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Brand::query()->active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $seo = (new Seo(title: 'Markalar'))->crumb('Ana Sayfa', route('home'))->crumb('Markalar');

        return view('brands.index', compact('brands', 'seo'));
    }

    public function show(Request $request, Brand $brand, ProductCatalog $catalog): View
    {
        abort_unless($brand->is_active, 404);

        $products = $catalog->query(null, ['marka' => [$brand->slug], 'sirala' => 'onerilen'])
            ->paginate(ProductCatalog::PER_PAGE)
            ->withQueryString();

        $seo = Seo::for($brand, $brand->cover_image ?: $brand->logo)
            ->crumb('Ana Sayfa', route('home'))
            ->crumb('Markalar', route('brands.index'))
            ->crumb($brand->name);

        return view('brands.show', compact('brand', 'products', 'seo'));
    }
}
