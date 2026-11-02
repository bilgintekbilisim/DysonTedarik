<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Ön yüz ürün listeleme: kategori (alt kategoriler ve ek kategoriler dahil), marka,
 * filtre özellikleri, arama ve sıralama. Filtre seçenekleri yalnızca mevcut sonuçlarda
 * karşılığı olan marka/değerlerden üretilir; boş sonuç veren seçenek gösterilmez.
 */
class ProductCatalog
{
    public const SORTS = [
        'onerilen' => 'Önerilen',
        'yeni' => 'En yeni',
        'ad' => 'Ada göre (A–Z)',
    ];

    public const PER_PAGE = 24;

    /**
     * @param  array{q?: ?string, marka?: array|string|null, sirala?: ?string, ozellik?: array<string, array|string>}  $filters
     *                                                                                                                           ozellik: ["renk" => ["beyaz", "gri"]] — aynı özellik içinde VEYA, özellikler arasında VE
     */
    public function query(?Category $category, array $filters = []): Builder
    {
        $query = Product::query()->active()->with(['brand', 'coverImage']);

        if ($category) {
            $this->inCategory($query, $category);
        }

        $query->search($filters['q'] ?? null);

        if ($brands = $this->list($filters['marka'] ?? null)) {
            $query->whereHas('brand', fn (Builder $b) => $b->whereIn('slug', $brands));
        }

        foreach ($filters['ozellik'] ?? [] as $attributeSlug => $valueSlugs) {
            if ($values = $this->list($valueSlugs)) {
                $query->whereHas('attributeValues', fn (Builder $v) => $v
                    ->whereIn('attribute_values.slug', $values)
                    ->whereHas('attribute', fn (Builder $a) => $a->where('slug', $attributeSlug)));
            }
        }

        return match ($filters['sirala'] ?? 'onerilen') {
            'yeni' => $query->latest('id'),
            'ad' => $query->orderBy('name'),
            default => $query->orderByDesc('is_featured')->orderBy('sort_order')->latest('id'),
        };
    }

    public function paginate(?Category $category, array $filters = []): LengthAwarePaginator
    {
        return $this->query($category, $filters)->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * Kategori sayfasındaki filtre paneli için seçenekler (yalnızca bu kategoride ürünü olanlar).
     *
     * @return array{brands: Collection, attributes: Collection, children: Collection}
     */
    public function facets(?Category $category): array
    {
        $productIds = Product::query()->active()
            ->when($category, fn (Builder $q) => $this->inCategory($q, $category))
            ->pluck('id');

        $brands = Brand::query()->active()
            ->whereHas('products', fn (Builder $q) => $q->whereIn('products.id', $productIds))
            ->withCount(['products' => fn (Builder $q) => $q->whereIn('products.id', $productIds)])
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $attributes = ProductAttribute::query()
            ->where('is_filterable', true)
            ->orderBy('sort_order')
            ->with(['values' => fn ($q) => $q
                ->whereHas('products', fn (Builder $p) => $p->whereIn('products.id', $productIds))
                ->withCount(['products' => fn (Builder $p) => $p->whereIn('products.id', $productIds)])])
            ->get()
            ->filter(fn (ProductAttribute $attribute) => $attribute->values->isNotEmpty())
            ->values();

        $children = $category
            ? $category->children()->active()->get(['id', 'name', 'slug', 'parent_id'])
            : Category::query()->active()->roots()->orderBy('sort_order')->get(['id', 'name', 'slug', 'parent_id']);

        return compact('brands', 'attributes', 'children');
    }

    private function inCategory(Builder $query, Category $category): void
    {
        $ids = Category::descendantIdsOf($category->id);

        $query->where(fn (Builder $q) => $q
            ->whereIn('category_id', $ids)
            ->orWhereHas('categories', fn (Builder $c) => $c->whereIn('categories.id', $ids)));
    }

    /** "a,b" veya ["a", "b"] → ["a", "b"]; yalnızca slug karakterleri kabul edilir. */
    private function list(array|string|null $value): array
    {
        $items = is_array($value) ? $value : explode(',', (string) $value);

        return collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn (string $item) => preg_match('/^[a-z0-9-]{1,100}$/', $item) === 1)
            ->unique()
            ->values()
            ->all();
    }
}
