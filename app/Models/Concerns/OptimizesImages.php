<?php

namespace App\Models\Concerns;

use App\Services\ImageOptimizer;

/**
 * Modelin $optimizedImages alanlarındaki görselleri kayıt sırasında WebP'ye çevirip boyutunu sınırlar.
 * $thumbnailImages alanları için ayrıca 480 px küçük görsel üretilir.
 *
 * Örnek: protected array $optimizedImages = ['logo' => 600, 'cover_image' => 1920];
 */
trait OptimizesImages
{
    /** @return array<string, int> */
    public function optimizedImages(): array
    {
        return $this->optimizedImages ?? [];
    }

    public static function bootOptimizesImages(): void
    {
        static::saving(function ($model) {
            $optimizer = app(ImageOptimizer::class);

            foreach ($model->optimizedImages ?? [] as $field => $maxSize) {
                if ($model->isDirty($field) && filled($model->{$field})) {
                    $model->{$field} = $optimizer->optimize($model->{$field}, $maxSize);
                }
            }
        });

        static::saved(function ($model) {
            foreach ($model->thumbnailImages ?? [] as $field) {
                if ($model->wasChanged($field) || $model->wasRecentlyCreated) {
                    filled($model->{$field}) && app(ImageOptimizer::class)->thumbnail($model->{$field});
                }
            }
        });
    }
}
