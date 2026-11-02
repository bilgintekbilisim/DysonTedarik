<?php

namespace App\Services;

use App\Enums\MenuLocation;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Cache;

/**
 * Menüleri ve mega menü kategori ağacını düz dizilere çevirip önbellekler.
 * Menü, sayfa, kategori veya marka değiştiğinde Menu::flushCache() ile temizlenir.
 */
class NavigationService
{
    public const CATEGORY_CACHE_KEY = 'menu.categories';

    /**
     * @return array<int, array{label: string, url: ?string, new_tab: bool, mega: bool, children: array}>
     */
    public function menu(MenuLocation $location): array
    {
        return Cache::rememberForever("menu.{$location->value}", function () use ($location) {
            $menu = Menu::query()->where('location', $location)->first();

            if (! $menu) {
                return [];
            }

            $items = $menu->items()->where('is_active', true)->with(['page', 'category', 'brand'])->get();

            return $this->branch($items, null, 0);
        });
    }

    private function branch($items, ?int $parentId, int $depth): array
    {
        if ($depth > 3) {
            return [];
        }

        return $items->where('parent_id', $parentId)
            ->map(function (MenuItem $item) use ($items, $depth) {
                $children = $this->branch($items, $item->id, $depth + 1);
                $url = $item->resolveUrl();

                // Hedefi yayında olmayan ve alt öğesi de bulunmayan bağlantıları gösterme.
                if (($url === null || $url === '#') && $children === [] && ! $item->is_mega) {
                    return null;
                }

                return [
                    'label' => $item->label,
                    'url' => $url === '#' ? null : $url,
                    'new_tab' => $item->open_in_new_tab,
                    'mega' => $item->is_mega,
                    'children' => $children,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Mega menü ve mobil menü için aktif, menüde gösterilen kategori ağacı (2 seviye).
     *
     * @return array<int, array{name: string, url: string, children: array}>
     */
    public function categories(): array
    {
        return Cache::rememberForever(self::CATEGORY_CACHE_KEY, function () {
            $categories = Category::query()
                ->active()
                ->where('show_in_menu', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'parent_id', 'name', 'slug']);

            return $categories->whereNull('parent_id')
                ->map(fn (Category $root) => [
                    'name' => $root->name,
                    'url' => route('categories.show', $root),
                    'children' => $categories->where('parent_id', $root->id)
                        ->map(fn (Category $child) => ['name' => $child->name, 'url' => route('categories.show', $child)])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all();
        });
    }
}
