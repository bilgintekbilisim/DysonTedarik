<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['product_id', 'path', 'alt', 'sort_order'])]
class ProductImage extends Model
{
    protected static function booted(): void
    {
        // Panelden kaldırılan dosya diskte yer kaplamaya devam etmesin.
        static::deleted(fn (ProductImage $file) => Storage::disk('public')->delete($file->path));
        static::updated(function (ProductImage $file) {
            if ($file->wasChanged('path') && $file->getOriginal('path')) {
                Storage::disk('public')->delete($file->getOriginal('path'));
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
