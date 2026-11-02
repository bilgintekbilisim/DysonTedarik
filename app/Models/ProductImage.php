<?php

namespace App\Models;

use App\Models\Concerns\OptimizesImages;
use App\Services\ImageOptimizer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['product_id', 'path', 'alt', 'sort_order'])]
class ProductImage extends Model
{
    use OptimizesImages;

    /** Kayıtta WebP'ye çevrilen görsel alanları => en uzun kenar (px). */
    protected array $optimizedImages = ['path' => 1200];

    protected array $thumbnailImages = ['path'];

    protected static function booted(): void
    {
        // Panelden kaldırılan dosya diskte yer kaplamaya devam etmesin.
        static::deleted(fn (ProductImage $file) => Storage::disk('public')->delete([$file->path, ImageOptimizer::thumbnailPath($file->path)]));
        static::updated(function (ProductImage $file) {
            if ($file->wasChanged('path') && $file->getOriginal('path')) {
                Storage::disk('public')->delete([$file->getOriginal('path'), ImageOptimizer::thumbnailPath($file->getOriginal('path'))]);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
