<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['attribute_id', 'value', 'slug', 'sort_order'])]
class AttributeValue extends Model
{
    protected static function booted(): void
    {
        static::saving(function (AttributeValue $value) {
            $value->slug = Str::slug($value->slug ?: $value->value, '-', 'tr');
        });
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
