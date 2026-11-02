<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\ProductImage;
use App\Models\Slide;
use App\Services\ImageOptimizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Mevcut görselleri toplu optimize eder ve eksik ürün küçük görsellerini üretir.
 * Yeni yüklemeler OptimizesImages ile zaten otomatik işlenir; bu komut eski kayıtlar içindir.
 */
#[Signature('images:optimize')]
#[Description('Görselleri WebP\'ye çevirir, boyutlarını sınırlar ve ürün küçük görsellerini üretir')]
class OptimizeImages extends Command
{
    public function handle(ImageOptimizer $optimizer): int
    {
        $changed = 0;

        foreach ([ProductImage::class, Slide::class, Category::class, Brand::class, Page::class] as $class) {
            $class::query()->withoutGlobalScopes()->chunkById(200, function ($records) use ($optimizer, &$changed) {
                foreach ($records as $record) {
                    foreach ($record->optimizedImages() as $field => $max) {
                        if (blank($record->{$field})) {
                            continue;
                        }

                        $new = $optimizer->optimize($record->{$field}, $max);

                        if ($new !== $record->{$field}) {
                            $record->forceFill([$field => $new])->saveQuietly();
                            $changed++;
                        }
                    }
                }
            });
        }

        $thumbs = 0;
        ProductImage::query()->chunkById(200, function ($images) use ($optimizer, &$thumbs) {
            foreach ($images as $image) {
                $optimizer->thumbnail($image->path) && $thumbs++;
            }
        });

        $this->info("{$changed} görsel optimize edildi, {$thumbs} küçük görsel üretildi.");

        return self::SUCCESS;
    }
}
