<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Slug boşsa kaynak alandan (varsayılan "name") Türkçe uyumlu, benzersiz slug üretir.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            $source = $model->slugSource ?? 'name';

            $slug = Str::slug($model->slug ?: (string) $model->{$source}, '-', 'tr');
            $model->slug = static::uniqueSlug($slug !== '' ? $slug : Str::lower(Str::random(8)), $model->getKey());
        });
    }

    public static function uniqueSlug(string $slug, mixed $ignoreId = null): string
    {
        $candidate = $slug;
        $i = 2;

        while (static::withoutGlobalScopes()
            ->when(method_exists(static::class, 'bootSoftDeletes'), fn ($q) => $q->withTrashed())
            ->where('slug', $candidate)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $candidate = "{$slug}-{$i}";
            $i++;
        }

        return $candidate;
    }
}
