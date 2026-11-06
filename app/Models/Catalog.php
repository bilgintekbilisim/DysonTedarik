<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\OptimizesImages;
use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/** E-Katalog: indirilebilir PDF ürün kataloğu veya broşür. */
#[Fillable(['title', 'slug', 'description', 'cover_image', 'file', 'file_size', 'brand_id', 'is_active', 'sort_order'])]
class Catalog extends Model
{
    use HasSlug, OptimizesImages, SoftDeletes;

    protected string $slugSource = 'title';

    /** Kayıtta WebP'ye çevrilen görsel alanları => en uzun kenar (px). */
    protected array $optimizedImages = ['cover_image' => 1000];

    protected array $thumbnailImages = ['cover_image'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Catalog $catalog) {
            if ($catalog->isDirty('file') && $catalog->file && Storage::disk('public')->exists($catalog->file)) {
                $catalog->file_size = Storage::disk('public')->size($catalog->file);
            }
        });

        static::saved(fn () => PublicCache::flush());
        static::deleted(fn () => PublicCache::flush());
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function fileUrl(): string
    {
        return Storage::disk('public')->url($this->file);
    }

    /** "2,4 MB" biçiminde dosya boyutu. */
    public function readableSize(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        return $this->file_size >= 1048576
            ? number_format($this->file_size / 1048576, 1, ',', '.').' MB'
            : max(1, (int) round($this->file_size / 1024)).' KB';
    }

    public function downloadName(): string
    {
        return $this->slug.'.pdf';
    }
}
