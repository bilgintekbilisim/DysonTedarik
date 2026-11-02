<?php

namespace App\Models;

use App\Enums\MenuItemType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;

#[Fillable([
    'menu_id', 'parent_id', 'label', 'type', 'page_id', 'category_id', 'brand_id', 'route_name',
    'url', 'open_in_new_tab', 'is_mega', 'sort_order', 'is_active',
])]
class MenuItem extends Model
{
    protected function casts(): array
    {
        return [
            'type' => MenuItemType::class,
            'open_in_new_tab' => 'boolean',
            'is_mega' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Menu::flushCache());
        static::deleted(fn () => Menu::flushCache());
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** Bağlantı hedefini çözümler; hedef silinmiş/yayında değilse null döner. */
    public function resolveUrl(): ?string
    {
        return match ($this->type) {
            MenuItemType::Url => $this->url ?: null,
            MenuItemType::Page => $this->page?->isPublished() ? $this->routeOrNull('pages.show', $this->page) : null,
            MenuItemType::Category => $this->category?->is_active ? $this->routeOrNull('categories.show', $this->category) : null,
            MenuItemType::Brand => $this->brand?->is_active ? $this->routeOrNull('brands.show', $this->brand) : null,
            MenuItemType::Route => $this->routeOrNull((string) $this->route_name),
            default => null,
        };
    }

    private function routeOrNull(string $name, mixed $parameter = null): ?string
    {
        if (! Route::has($name)) {
            return null;
        }

        return $parameter ? route($name, $parameter) : route($name);
    }
}
