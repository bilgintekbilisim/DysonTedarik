<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Services\HtmlSanitizer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'slug', 'logo', 'cover_image', 'description', 'website', 'is_featured', 'is_active', 'sort_order',
    'meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex',
])]
class Brand extends Model
{
    use HasFactory, HasSeo, HasSlug, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Menü bağlantıları ve mega menü bu kayıtların yayın durumuna bağlı.
        static::saved(fn () => Menu::flushCache());
        static::deleted(fn () => Menu::flushCache());

        static::saving(function (Brand $brand) {
            $brand->description = app(HtmlSanitizer::class)->clean($brand->description);
        });
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
