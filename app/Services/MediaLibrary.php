<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Category;
use App\Models\Media;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Models\Slide;
use App\Support\PublicCache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Medya kütüphanesi ile yükleme klasörünü eşitler ve bir dosyanın sitede nerede kullanıldığını bulur.
 * Kütüphaneden silinen dosya, kullanıldığı kayıtlardan da kaldırılır (zorunlu alanlıysa kayıt silinir).
 */
class MediaLibrary
{
    /** Dosyaya başvuran alanlar: model => [sütun => zorunlu mu]. */
    private const REFERENCES = [
        ProductImage::class => ['path' => true],
        ProductDocument::class => ['path' => true],
        Product::class => ['og_image' => false],
        Category::class => ['image' => false, 'og_image' => false],
        Brand::class => ['logo' => false, 'cover_image' => false, 'og_image' => false],
        Page::class => ['cover_image' => false, 'og_image' => false],
        Slide::class => ['image_desktop' => true, 'image_mobile' => false],
        Catalog::class => ['file' => true, 'cover_image' => false],
    ];

    /** Klasör adı => kütüphane klasörü. */
    private const FOLDERS = [
        'products' => 'urun', 'categories' => 'urun', 'brands' => 'urun',
        'slides' => 'banner', 'blocks' => 'banner',
        'pages' => 'kurumsal', 'settings' => 'kurumsal',
        'catalogs' => 'dokuman',
    ];

    private const SKIP = '#(^|/)(\.|livewire-tmp/|backups/)|-480\.webp$#';

    /** Diskte olup kütüphanede olmayan dosyaları ekler, diskten silinmiş kayıtları kaldırır. @return array{added: int, removed: int} */
    public function sync(): array
    {
        $disk = Storage::disk('public');
        $files = collect($disk->allFiles())->reject(fn (string $path) => preg_match(self::SKIP, $path) === 1)->values();
        $known = Media::query()->pluck('path')->flip();

        $added = 0;
        foreach ($files as $path) {
            if (! isset($known[$path])) {
                Media::query()->create([
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => basename($path),
                    'folder' => self::FOLDERS[Str::before($path, '/')] ?? 'genel',
                ]);
                $added++;
            }
        }

        $existing = $files->flip();
        $missing = Media::query()->get(['id', 'path'])->reject(fn (Media $m) => isset($existing[$m->path]));
        Media::withoutEvents(fn () => Media::query()->whereKey($missing->modelKeys())->delete());

        return ['added' => $added, 'removed' => $missing->count()];
    }

    /** @return list<string> "Ürün görseli: Metal Kesme Diskleri" gibi okunur kullanım yerleri */
    public function usages(string $path): array
    {
        $found = [];

        foreach (self::REFERENCES as $model => $columns) {
            $query = $model::query()->when(method_exists($model, 'bootSoftDeletes'), fn ($q) => $q->withTrashed());
            $query->where(fn ($q) => collect($columns)->keys()->each(fn ($c) => $q->orWhere($c, $path)));

            foreach ($query->get() as $record) {
                $found[] = $this->label($record);
            }
        }

        foreach (Setting::query()->get() as $setting) {
            if ($setting->value === $path) {
                $found[] = 'Site ayarı: '.$setting->key;
            }
        }

        // Bloklar JSON'da tutulur; "/" kaçışlı da yazılmış olabilir.
        $inBlocks = fn (string $needle) => Page::query()->withTrashed()
            ->where(fn ($q) => $q->where('blocks', 'like', '%'.addcslashes($needle, '%_\\').'%')->orWhere('draft_blocks', 'like', '%'.addcslashes($needle, '%_\\').'%'))
            ->exists();
        if ($inBlocks($path) || $inBlocks(str_replace('/', '\\/', $path))) {
            $found[] = 'Ana sayfa / sayfa blokları';
        }

        return $found;
    }

    /** Dosyayı ve küçük görselini siler; başvuran alanları boşaltır, zorunlu alanlı kayıtları siler. */
    public function purge(string $path): void
    {
        foreach (self::REFERENCES as $model => $columns) {
            foreach ($columns as $column => $required) {
                $query = $model::query()->when(method_exists($model, 'bootSoftDeletes'), fn ($q) => $q->withTrashed())->where($column, $path);
                $required
                    ? $query->get()->each(fn ($record) => method_exists($record, 'forceDelete') ? $record->forceDelete() : $record->delete())
                    : $query->update([$column => null]);
            }
        }

        Setting::query()->get()->filter(fn (Setting $s) => $s->value === $path)->each(fn (Setting $s) => $s->update(['value' => null]));
        app(SettingsService::class)->flush();

        $disk = Storage::disk('public');
        $disk->delete([$path, ImageOptimizer::thumbnailPath($path)]);
        PublicCache::flush();
    }

    private function label(object $record): string
    {
        return match ($record::class) {
            ProductImage::class => 'Ürün görseli: '.($record->product?->name ?? '#'.$record->product_id),
            ProductDocument::class => 'Ürün dokümanı: '.($record->product?->name ?? '#'.$record->product_id),
            Product::class => 'Ürün: '.$record->name,
            Category::class => 'Kategori: '.$record->name,
            Brand::class => 'Marka: '.$record->name,
            Page::class => 'Sayfa: '.$record->title,
            Slide::class => 'Slayt: '.($record->title ?: '#'.$record->id),
            Catalog::class => 'E-katalog: '.$record->title,
            default => class_basename($record).' #'.$record->getKey(),
        };
    }
}
