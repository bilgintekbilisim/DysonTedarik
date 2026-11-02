<?php

namespace App\Support\Blocks;

use App\Enums\SlideGroup;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slide;
use Illuminate\Support\Collection;

/**
 * Blokların veritabanından ihtiyaç duyduğu kayıtları toplar; Blade şablonları sorgu yapmaz.
 * Kaydı olmayan dinamik bloklar (ör. hiç slayt yoksa slider) null döner ve çizilmez.
 */
class BlockData
{
    private const MAX_LIMIT = 24;

    /** @return array<string, mixed>|null */
    public function resolve(string $type, array $data): ?array
    {
        $limit = max(1, min(self::MAX_LIMIT, (int) ($data['limit'] ?? 8)));

        $extra = match ($type) {
            'hero_slider' => ['slides' => Slide::query()->visible()->where('group', SlideGroup::Hero)->get()],
            'promo_banners' => ['slides' => Slide::query()->visible()->where('group', SlideGroup::Promo)->limit($limit)->get()],
            'category_cards' => ['categories' => $this->categories($data['category_ids'] ?? [], $limit)],
            'featured_products' => ['products' => $this->products()->where('is_featured', true)->orderBy('sort_order')->latest('id')->limit($limit)->get()],
            'latest_products' => ['products' => $this->products()->latest('id')->limit($limit)->get()],
            'featured_brands' => ['brands' => Brand::query()->active()->where('is_featured', true)->orderBy('sort_order')->orderBy('name')->limit($limit)->get()],
            'features', 'stats' => ['items' => collect($data['items'] ?? [])->filter(fn ($item) => filled($item['title'] ?? $item['label'] ?? null))->values()],
            default => [],
        };

        $empty = collect($extra)->contains(fn ($value) => $value instanceof Collection && $value->isEmpty());

        if ($empty || ($type === 'about_intro' && blank($data['content'] ?? null) && blank($data['title'] ?? null))) {
            return null;
        }

        return [...$data, ...$extra];
    }

    private function products()
    {
        return Product::query()->active()->with(['brand', 'coverImage']);
    }

    private function categories(array $ids, int $limit): Collection
    {
        $query = Category::query()->active()->withCount('primaryProducts');

        if ($ids !== []) {
            $categories = $query->whereIn('id', $ids)->get()->keyBy('id');

            // Yöneticinin seçtiği sırayı koru.
            return collect($ids)->map(fn ($id) => $categories->get((int) $id))->filter()->take($limit)->values();
        }

        return $query->roots()->orderBy('sort_order')->limit($limit)->get();
    }
}
