<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\OptimizesImages;
use App\Models\Concerns\RedirectsOldSlug;
use App\Services\HtmlSanitizer;
use App\Support\PublicCache;
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
    use HasFactory, HasSeo, HasSlug, OptimizesImages, RedirectsOldSlug, SoftDeletes;

    /** Kayıtta WebP'ye çevrilen görsel alanları => en uzun kenar (px). */
    protected array $optimizedImages = ['logo' => 600, 'cover_image' => 1920, 'og_image' => 1200];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Menüler, mega menü ve sitemap bu kayıtların yayın durumuna bağlı.
        static::saved(function (Brand $brand) {
            PublicCache::flush();
            if ($brand->wasChanged('name')) {
                Product::reindexSearch(Product::query()->where('brand_id', $brand->id));
            }
        });
        static::deleted(fn () => PublicCache::flush());

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

    /** Slug değişince otomatik 301 için ön yüz rotası. */
    public function publicRouteName(): ?string
    {
        return 'brands.show';
    }
}
