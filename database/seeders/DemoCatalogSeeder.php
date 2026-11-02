<?php

namespace Database\Seeders;

use App\Enums\SlideGroup;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Slide;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * YALNIZCA YEREL GELİŞTİRME İÇİN: ön yüzü görsel olarak test etmek üzere "Demo" etiketli
 * kategori, marka, ürün ve slaytlar oluşturur. Gerçek marka/firma bilgisi içermez.
 * DatabaseSeeder'dan çağrılmaz:  php artisan db:seed --class=DemoCatalogSeeder
 */
class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo verisi production ortamında oluşturulamaz.');
        }

        $tree = [
            'Silikonlar' => ['Nötr Silikonlar', 'Asetik Silikonlar', 'Yüksek Isı Silikonları'],
            'Mastikler' => ['Akrilik Mastikler', 'Poliüretan Mastikler', 'Hibrit Mastikler'],
            'Köpükler' => ['Montaj Köpükleri', 'Yangına Dayanıklı Köpükler'],
            'Yapıştırıcılar' => ['Montaj Yapıştırıcıları', 'Epoksi Yapıştırıcılar'],
            'Yapı Kimyasalları' => ['Su Yalıtımı', 'Derz Dolguları'],
            'Bağlantı Elemanları' => ['Dübeller', 'Vidalar', 'Ankrajlar'],
        ];

        $leaves = [];
        $order = 0;

        foreach ($tree as $root => $children) {
            $parent = Category::query()->firstOrCreate(['name' => $root], ['is_active' => true, 'sort_order' => ++$order, 'summary' => "Demo {$root} kategorisi."]);

            foreach ($children as $child) {
                $leaves[] = Category::query()->firstOrCreate(['name' => $child], ['parent_id' => $parent->id, 'is_active' => true]);
            }
        }

        $brands = collect(['Demo Marka A', 'Demo Marka B', 'Demo Marka C', 'Demo Marka D'])
            ->map(fn ($name, $i) => Brand::query()->firstOrCreate(['name' => $name], ['is_active' => true, 'is_featured' => true, 'sort_order' => $i]));

        $color = ProductAttribute::query()->firstOrCreate(['name' => 'Renk'], ['is_filterable' => true, 'sort_order' => 1]);
        $volume = ProductAttribute::query()->firstOrCreate(['name' => 'Hacim'], ['is_filterable' => true, 'sort_order' => 2, 'unit' => 'ml']);
        $colors = collect(['Beyaz', 'Şeffaf', 'Gri', 'Siyah'])->map(fn ($v) => AttributeValue::query()->firstOrCreate(['attribute_id' => $color->id, 'slug' => str($v)->slug('-', 'tr')], ['value' => $v]));
        $volumes = collect(['280 ml', '310 ml', '600 ml'])->map(fn ($v) => AttributeValue::query()->firstOrCreate(['attribute_id' => $volume->id, 'slug' => str($v)->slug('-', 'tr')], ['value' => $v]));

        foreach (range(1, 36) as $i) {
            $category = $leaves[$i % count($leaves)];
            $product = Product::query()->firstOrCreate(['code' => sprintf('DEMO-%03d', $i)], [
                'name' => "Demo {$category->name} Ürünü {$i}",
                'brand_id' => $brands[$i % 4]->id,
                'category_id' => $category->id,
                'short_description' => 'Ön yüz testleri için oluşturulmuş örnek ürün. Gerçek ürün bilgisi değildir.',
                'description' => '<p>Bu bir <strong>demo</strong> ürün açıklamasıdır.</p><ul><li>Örnek madde 1</li><li>Örnek madde 2</li></ul>',
                'usage_areas' => '<p>Örnek kullanım alanları metni.</p>',
                'technical_specs' => [['label' => 'Örnek özellik', 'value' => 'Örnek değer'], ['label' => 'Ambalaj', 'value' => 'Koli']],
                'volume' => $volumes[$i % 3]->value,
                'color' => $colors[$i % 4]->value,
                'is_featured' => $i % 5 === 0,
                'is_active' => true,
            ]);
            $product->attributeValues()->syncWithoutDetaching([$colors[$i % 4]->id, $volumes[$i % 3]->id]);

            if ($product->images()->doesntExist() && $i <= 12) {
                $product->images()->create(['path' => $this->image("demo/urun-{$i}.png", 800, 800, "DEMO-{$i}", [241, 244, 250]), 'alt' => $product->name]);
            }
        }

        if (Slide::query()->doesntExist()) {
            Slide::query()->create([
                'group' => SlideGroup::Hero, 'title' => 'Demo slayt — başlık panelden değiştirilir',
                'description' => 'Bu slayt yalnızca yerel ön yüz testi içindir.', 'button_text' => 'Ürünleri incele', 'button_url' => '/urunler',
                'image_desktop' => $this->image('demo/slayt-1.png', 1920, 700, 'DEMO SLIDER', [18, 32, 63]), 'sort_order' => 1,
            ]);
            Slide::query()->create([
                'group' => SlideGroup::Hero, 'title' => 'İkinci demo slayt',
                'image_desktop' => $this->image('demo/slayt-2.png', 1920, 700, 'DEMO SLIDER 2', [37, 58, 107]), 'sort_order' => 2,
            ]);
            Slide::query()->create([
                'group' => SlideGroup::Promo, 'title' => 'Demo tanıtım bannerı', 'button_text' => 'İncele', 'button_url' => '/urunler',
                'image_desktop' => $this->image('demo/banner-1.png', 960, 420, 'DEMO BANNER', [154, 52, 18]), 'sort_order' => 1,
            ]);
        }
    }

    /** GD ile düz renkli yer tutucu görsel üretir. */
    private function image(string $path, int $width, int $height, string $label, array $rgb): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, ...$rgb));
        $textColor = array_sum($rgb) > 500 ? imagecolorallocate($image, 62, 92, 159) : imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 5, (int) ($width / 2 - strlen($label) * 4.5), (int) ($height / 2 - 8), $label, $textColor);

        ob_start();
        imagepng($image);
        Storage::disk('public')->put($path, ob_get_clean());
        imagedestroy($image);

        return $path;
    }
}
