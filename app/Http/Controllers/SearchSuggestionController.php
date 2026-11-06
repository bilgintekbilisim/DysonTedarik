<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Arama kutusu önerileri: ad, kod veya marka ile en fazla 8 ürün. */
class SearchSuggestionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $products = Product::query()->active()
            ->searchOrSimilar($term)
            ->with(['brand:id,name', 'coverImage'])
            ->orderByRaw('CASE WHEN code = ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END', [$term, addcslashes($term, '%_\\').'%'])
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'slug', 'code', 'brand_id']);

        return response()->json([
            'data' => $products->map(fn (Product $product) => [
                'name' => $product->name,
                'code' => $product->code,
                'brand' => $product->brand?->name,
                'url' => route('products.show', $product),
                'image' => $product->coverImage ? Storage::disk('public')->url($product->coverImage->path) : null,
            ]),
            'all_url' => route('products.index', ['q' => $term]),
        ]);
    }
}
