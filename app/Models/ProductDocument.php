<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['product_id', 'title', 'path', 'sort_order'])]
class ProductDocument extends Model
{
    protected static function booted(): void
    {
        // Panelden kaldırılan dosya diskte yer kaplamaya devam etmesin.
        static::deleted(fn (ProductDocument $file) => Storage::disk('public')->delete($file->path));
        static::updated(function (ProductDocument $file) {
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
