<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Filtrelenebilir ürün özelliği (ör. Renk, Hacim). Tablo: attributes */
#[Fillable(['name', 'slug', 'unit', 'is_filterable', 'sort_order'])]
class ProductAttribute extends Model
{
    use HasSlug;

    protected $table = 'attributes';

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id')->orderBy('sort_order');
    }
}
