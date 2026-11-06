<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\OptimizesImages;
use App\Models\Concerns\RedirectsOldSlug;
use App\Services\HtmlSanitizer;
use App\Support\PublicCache;
use App\Support\SearchText;
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
use Illuminate\Support\Facades\Schema;

#[Fillable([
    'brand_id', 'category_id', 'name', 'code', 'slug', 'short_description', 'description', 'usage_areas',
    'technical_specs', 'variant_table', 'color', 'volume', 'packaging', 'is_featured', 'is_active', 'published_at', 'sort_order',
    'meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex',
])]
class Product extends Model
{
    use HasFactory, HasSeo, HasSlug, OptimizesImages, RedirectsOldSlug, SoftDeletes;

    /** Kayıtta WebP'ye çevrilen görsel alanları => en uzun kenar (px). */
    protected array $optimizedImages = ['og_image' => 1200];

    protected function casts(): array
    {
        return [
            'technical_specs' => 'array',
            'variant_table' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => PublicCache::flush());
        static::deleted(fn () => PublicCache::flush());

        static::saving(function (Product $product) {
            $sanitizer = app(HtmlSanitizer::class);
            $product->description = $sanitizer->clean($product->description);
            $product->usage_areas = $sanitizer->clean($product->usage_areas);
            $product->code = trim((string) $product->code);
            // Eski migration'lar (ör. Karbosan aktarımı) sütun eklenmeden önce ürün kaydedebilir.
            if (static::$hasSearchColumn ??= Schema::hasColumn('products', 'search_text')) {
                $product->search_text = $product->buildSearchText();
            }
        });
    }

    private static ?bool $hasSearchColumn = null;

    /**
     * Aramada kullanılan normalleştirilmiş metin: ad, kod, marka, kategori, kısa açıklama ve ebat/artikel tablosu;
     * sonuna ad ve kodun boşluksuz hâli eklenir ("metalkesme" → "Metal Kesme Diskleri").
     */
    public function buildSearchText(): string
    {
        $cells = collect($this->variant_table['rows'] ?? [])->flatten()->implode(' ');

        return mb_substr(implode(' ', array_filter([
            SearchText::normalize($this->name),
            SearchText::normalize($this->code),
            SearchText::normalize(Brand::query()->whereKey($this->brand_id)->value('name')),
            SearchText::normalize(Category::query()->whereKey($this->category_id)->value('name')),
            SearchText::normalize(strip_tags((string) $this->short_description)),
            SearchText::normalize($cells),
            SearchText::compact($this->name),
            SearchText::compact($this->code),
        ])), 0, 60000);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /**
     * Ad, kod, marka, kategori, açıklama ve ebat/artikel tablosunda arama. Büyük/küçük harf, Türkçe karakter,
     * boşluk ve kelime sırası fark etmez: her kelime metinde geçmeli ("kesme metal", "METAL KESME", "metalkesme").
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $words = SearchText::words($term);

        if ($words === []) {
            // Yalnızca işaretlerden oluşan arama ("%", "-") hiçbir ürünle eşleşmez.
            trim((string) $term) !== '' && $query->whereRaw('1 = 0');

            return;
        }

        $like = fn (string $s) => '%'.addcslashes($s, '%_\\').'%';

        $query->where(fn (Builder $q) => $q
            ->where(function (Builder $all) use ($words, $like) {
                foreach ($words as $word) {
                    $all->where('search_text', 'like', $like($word));
                }
            })
            ->orWhere('search_text', 'like', $like(SearchText::compact($term))));
    }

    /**
     * Önce birebir arama; hiç sonuç yoksa yazım hatasına dayanıklı benzer ürünler ("antfiriz" → Antifriz).
     */
    #[Scope]
    protected function searchOrSimilar(Builder $query, ?string $term): void
    {
        $words = SearchText::words($term);

        if ($words === []) {
            $query->search($term);

            return;
        }

        if (static::query()->active()->search($term)->exists()) {
            $query->search($term);

            return;
        }

        $ids = static::similarIds($words);
        $query->whereIn('products.id', $ids);

        // En benzer ürün önce.
        if ($ids !== []) {
            $query->orderByRaw('CASE products.id '.implode(' ', array_map(fn (int $id, int $i) => "WHEN {$id} THEN {$i}", $ids, array_keys($ids))).' END');
        }
    }

    /** Marka / kategori adı değişince ilgili ürünlerin arama metnini yeniler. */
    public static function reindexSearch(Builder $query): void
    {
        static::$hasSearchColumn = null;
        $query->withTrashed()->chunkById(200, function ($products) {
            foreach ($products as $product) {
                $product->search_text = $product->buildSearchText();
                $product->saveQuietly();
            }
        });
    }

    /**
     * @param  list<string>  $words
     * @return list<int> benzerlik sırasına göre ürün kimlikleri
     */
    public static function similarIds(array $words, int $limit = 200): array
    {
        return static::query()->active()->get(['id', 'search_text'])
            ->map(fn (Product $p) => [$p->id, SearchText::fuzzyScore($words, explode(' ', (string) $p->search_text))])
            ->filter(fn (array $row) => $row[1] !== null)
            ->sortBy(fn (array $row) => $row[1])
            ->take($limit)
            ->map(fn (array $row) => $row[0])
            ->values()
            ->all();
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

    /** Slug değişince otomatik 301 için ön yüz rotası. */
    public function publicRouteName(): ?string
    {
        return 'products.show';
    }
}
