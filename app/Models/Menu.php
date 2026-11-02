<?php

namespace App\Models;

use App\Enums\MenuLocation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable(['name', 'location'])]
class Menu extends Model
{
    protected function casts(): array
    {
        return [
            'location' => MenuLocation::class,
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    public static function flushCache(): void
    {
        foreach (MenuLocation::cases() as $location) {
            Cache::forget("menu.{$location->value}");
        }
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id');
    }
}
