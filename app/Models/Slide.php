<?php

namespace App\Models;

use App\Enums\SlideGroup;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'group', 'title', 'description', 'image_desktop', 'image_mobile', 'image_alt',
    'button_text', 'button_url', 'sort_order', 'is_active', 'starts_at', 'ends_at',
])]
class Slide extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'group' => SlideGroup::class,
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** Aktif ve yayın tarih aralığı içindeki kayıtlar. */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('sort_order');
    }
}
