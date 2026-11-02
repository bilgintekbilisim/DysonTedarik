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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'brand_id', 'category_id', 'name', 'code', 'slug', 'short_description', 'description', 'usage_areas',
    'technical_specs', 'color', 'volume', 'packaging', 'is_featured', 'is_active', 'published_at', 'sort_order',
    'meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex',
])]
class Product extends Model
{
    use HasFactory, HasSeo, HasSlug, SoftDeletes;

    protected function casts(): array
    {
        return [
            'technical_specs' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            $sanitizer = app(HtmlSanitizer::class);
            $product->description = $sanitizer->clean($product->description);
            $product->usage_areas = $sanitizer->clean($product->usage_areas);
            $product->code = trim((string) $product->code);
        });
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /** Ürün adı, kodu veya marka adına göre arama. */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        $query->where(fn (Builder $q) => $q
            ->where('name', 'like', $like)
            ->orWhere('code', 'like', $like)
            ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', $like)));
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function coverImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->orderBy('sort_order');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProductDocument::class)->orderBy('sort_order');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class);
    }

    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }
}
