<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Catalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Örnek e-katalog: sitedeki Dyson Professional ürünlerinden üretilmiş PDF (assets/e-katalog).
 * Hiç katalog yoksa eklenir; panelden silinen veya değiştirilen kataloğa dokunulmaz.
 */
class ECatalogSeeder extends Seeder
{
    private const ASSETS = __DIR__.'/assets/e-katalog';

    public function run(): void
    {
        if (Catalog::withTrashed()->exists()) {
            return;
        }

        $disk = Storage::disk('public');
        $disk->put('catalogs/dyson-professional-urun-katalogu.pdf', file_get_contents(self::ASSETS.'/dyson-professional-urun-katalogu.pdf'));
        $disk->put('catalogs/dyson-professional-urun-katalogu-kapak.jpg', file_get_contents(self::ASSETS.'/dyson-professional-urun-katalogu-kapak.jpg'));

        Catalog::query()->create([
            'title' => 'Dyson Professional Ürün Kataloğu',
            'description' => 'AdBlue, antifriz, oto cam suyu, bakım spreyleri, silikon ve temizlik ürünleri; ürün kodları ve ambalaj seçenekleriyle.',
            'file' => 'catalogs/dyson-professional-urun-katalogu.pdf',
            'cover_image' => 'catalogs/dyson-professional-urun-katalogu-kapak.jpg',
            'brand_id' => Brand::query()->where('name', 'like', 'Dyson%')->value('id'),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }
}
