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
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'parent_id', 'name', 'slug', 'summary', 'description', 'image', 'is_active', 'show_in_menu', 'sort_order',
    'meta_title', 'meta_description', 'og_image', 'canonical_url', 'noindex',
])]
class Category extends Model
{
    use HasFactory, HasSeo, HasSlug, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            $category->description = app(HtmlSanitizer::class)->clean($category->description);
        });

        // Silinen kategorinin alt kategorileri bir üst seviyeye taşınır; ağaçta yetim kalmazlar.
        static::deleting(function (Category $category) {
            static::query()->where('parent_id', $category->id)->update(['parent_id' => $category->parent_id]);
        });
    }

    /** Bu kategori ve tüm alt kategorilerinin kimlikleri (filtrelerde kullanılır). */
    public static function descendantIdsOf(int $id): array
    {
        $all = static::query()->pluck('parent_id', 'id');
        $ids = [$id];
        $queue = [$id];

        while ($queue) {
            $current = array_shift($queue);

            foreach ($all as $childId => $parentId) {
                if ((int) $parentId === $current && ! in_array($childId, $ids, true)) {
                    $ids[] = $childId;
                    $queue[] = $childId;
                }
            }
        }

        return $ids;
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function roots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** Bu kategoriyi ana kategori olarak kullanan ürünler. */
    public function primaryProducts(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** Ek kategori olarak bağlı ürünler. */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** Kök kategoriden bu kategoriye kadar olan zincir (breadcrumb). */
    public function ancestorsAndSelf(): array
    {
        $chain = [$this];
        $current = $this;
        $guard = 0;

        while ($current->parent && $guard++ < 10) {
            $current = $current->parent;
            array_unshift($chain, $current);
        }

        return $chain;
    }

    /** Admin listelerinde "Yapı Kimyasalları › Silikonlar" biçiminde ad. */
    public function fullName(): string
    {
        return collect($this->ancestorsAndSelf())->pluck('name')->implode(' › ');
    }
}
