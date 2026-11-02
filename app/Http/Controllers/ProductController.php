<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\SettingsService;
use App\Support\Seo;
use App\Support\WhatsApp;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __invoke(Product $product, SettingsService $settings): View
    {
        abort_unless($product->is_active && ($product->published_at === null || $product->published_at->isPast()), 404);

        $product->load(['brand', 'category.parent.parent', 'images', 'documents', 'attributeValues.attribute']);

        $related = Product::query()->active()
            ->with(['brand', 'coverImage'])
            ->whereKeyNot($product->id)
            ->where('category_id', $product->category_id)
            ->inRandomOrder()
            ->limit(8)
            ->get();

        $cover = $product->images->first()?->path;
        $seo = Seo::for($product, $cover)->crumb('Ana Sayfa', route('home'))->crumb('Ürünler', route('products.index'));

        foreach ($product->category?->ancestorsAndSelf() ?? [] as $category) {
            $seo->crumb($category->name, route('categories.show', $category));
        }

        $seo->crumb($product->name);
        $seo->type = 'product';

        // Fiyat gösterilmediği için "offers" eklenmez; Product işaretlemesi ürün bilgisini tanımlar.
        $seo->addSchema(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $product->code,
            'mpn' => $product->code,
            'description' => $seo->description,
            'image' => $product->images->map(fn ($image) => url(Storage::disk('public')->url($image->path)))->all() ?: null,
            'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
            'category' => $product->category?->name,
            'color' => $product->color,
        ]));

        $whatsapp = WhatsApp::link("Merhaba, {$product->name} ({$product->code}) hakkında bilgi almak istiyorum.\n".route('products.show', $product));

        return view('catalog.product', compact('product', 'related', 'seo', 'whatsapp'));
    }
}
