<?php

namespace Database\Seeders;

use App\Enums\SlideGroup;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Slide;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dyson Professional ürün kataloğu, logo ve ana sayfa slaytları.
 * Ürün metinleri ambalaj etiketlerinden aktarılmıştır; yönetici panelden düzenleyebilir.
 * Tekrar çalıştırılabilir: kayıtlar ürün koduna / ada göre eşleşir, panelde yapılan değişiklikler ezilmez.
 *
 *   php artisan db:seed --class=DysonCatalogSeeder
 */
class DysonCatalogSeeder extends Seeder
{
    private const ASSETS = __DIR__.'/assets/dyson';

    /** @var array<string, Category> */
    private array $categories = [];

    public function run(): void
    {
        $this->logoAndSettings();
        $brand = $this->brand();
        $this->categories();

        foreach ($this->products() as $index => $data) {
            $this->product($brand, $data, $index);
        }

        $this->slides();
        $this->homepage();
    }

    private function asset(string $relative, string $target): string
    {
        Storage::disk('public')->put($target, file_get_contents(self::ASSETS.'/'.$relative));

        return $target;
    }

    private function logoAndSettings(): void
    {
        $settings = app(SettingsService::class);
        $current = $settings->all();

        $values = [];
        if (empty($current['general.logo'])) {
            $values['logo'] = $this->asset('logo.png', 'settings/dyson-professional-logo.png');
            $values['logo_dark'] = $values['logo'];
        }
        if (empty($current['general.favicon'])) {
            $values['favicon'] = $this->asset('favicon.png', 'settings/favicon.png');
        }

        if ($values) {
            $settings->setMany(['general' => $values]);
        }
    }

    private function brand(): Brand
    {
        $brand = Brand::withTrashed()->firstOrCreate(['name' => 'Dyson Professional'], [
            'description' => '<p>Dyson Professional; oto bakım sıvıları, bakım ve temizlik spreyleri, yapıştırma ve sızdırmazlık ürünleri ile endüstriyel temizlik ürünlerinden oluşan ürün ailesidir.</p>',
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 1,
        ]);

        if (! $brand->logo) {
            $brand->update(['logo' => $this->asset('logo.png', 'brands/dyson-professional.png')]);
        }

        return $brand;
    }

    private function categories(): void
    {
        $tree = [
            'Oto Bakım Sıvıları' => ['summary' => 'AdBlue, antifriz ve antifrizli oto cam suyu.', 'children' => ['AdBlue', 'Antifriz', 'Oto Cam Suyu']],
            'Bakım ve Temizlik Spreyleri' => ['summary' => 'Yağlayıcı, pas sökücü ve temizleyici spreyler.', 'children' => ['Yağlayıcı ve Pas Sökücü Spreyler', 'Temizleyici Spreyler']],
            'Yapıştırma ve Sızdırmazlık' => ['summary' => 'Sıcak silikon çubuklar ve RTV sıvı contalar.', 'children' => ['Mum Silikon', 'Sıvı Contalar']],
            'Endüstriyel Temizlik' => ['summary' => 'Atölye ve sanayi için el temizleme ürünleri.', 'children' => ['El Temizleme Ürünleri']],
        ];

        $order = 0;
        foreach ($tree as $root => $meta) {
            $parent = Category::withTrashed()->firstOrCreate(
                ['name' => $root, 'parent_id' => null],
                ['summary' => $meta['summary'], 'is_active' => true, 'show_in_menu' => true, 'sort_order' => ++$order],
            );
            $this->categories[$root] = $parent;

            foreach ($meta['children'] as $i => $child) {
                $this->categories[$child] = Category::withTrashed()->firstOrCreate(
                    ['name' => $child, 'parent_id' => $parent->id],
                    ['is_active' => true, 'show_in_menu' => true, 'sort_order' => $i + 1],
                );
            }
        }
    }

    private function attributeValue(string $attribute, string $value, ?string $unit = null): int
    {
        $attr = ProductAttribute::query()->firstOrCreate(
            ['slug' => Str::slug($attribute, '-', 'tr')],
            ['name' => $attribute, 'unit' => $unit, 'is_filterable' => true],
        );

        return AttributeValue::query()->firstOrCreate(
            ['attribute_id' => $attr->id, 'slug' => Str::slug($value, '-', 'tr')],
            ['value' => $value],
        )->id;
    }

    private function product(Brand $brand, array $data, int $index): void
    {
        $product = Product::withTrashed()->where('code', $data['code'])->first();

        if ($product) {
            return; // Panelde düzenlenmiş olabilir; mevcut ürüne dokunma.
        }

        $product = Product::query()->create([
            'brand_id' => $brand->id,
            'category_id' => $this->categories[$data['category']]->id,
            'name' => $data['name'],
            'code' => $data['code'],
            'short_description' => $data['short'],
            'description' => $data['description'],
            'usage_areas' => $data['usage'] ?? null,
            'technical_specs' => collect($data['specs'])->map(fn ($value, $label) => ['label' => $label, 'value' => $value])->values()->all(),
            'color' => $data['color'] ?? null,
            'volume' => $data['size'],
            'packaging' => $data['packaging'] ?? null,
            'is_featured' => $data['featured'] ?? false,
            'is_active' => true,
            'sort_order' => $index + 1,
        ]);

        $values = [$this->attributeValue('Ambalaj Boyutu', $data['size'])];
        if ($data['color'] ?? null) {
            $values[] = $this->attributeValue('Renk', $data['color']);
        }
        $product->attributeValues()->sync($values);

        $product->images()->create([
            'path' => $this->asset("products/{$data['image']}.webp", "products/dyson/{$data['image']}.webp"),
            'alt' => $data['name'],
            'sort_order' => 0,
        ]);
    }

    private function slides(): void
    {
        $slides = [
            [
                'file' => 'slayt-oto-bakim', 'category' => 'Oto Bakım Sıvıları',
                'title' => 'Araçlarınız için profesyonel bakım sıvıları',
                'description' => 'AdBlue, antifriz ve antifrizli oto cam suyunda 1 litreden 1000 litre IBC tanka kadar ambalaj seçenekleri.',
                'button' => 'Oto bakım ürünleri',
                'alt' => 'Dyson Professional AdBlue, antifriz ve oto cam suyu ürünleri',
            ],
            [
                'file' => 'slayt-bakim-spreyleri', 'category' => 'Bakım ve Temizlik Spreyleri',
                'title' => 'Atölye ve servisler için bakım spreyleri',
                'description' => 'Multi sprey, pas sökücü, sıvı gres, motor ve fren balata temizleyici.',
                'button' => 'Spreyleri incele',
                'alt' => 'Dyson Professional multi sprey, pas sökücü, sıvı gres, motor ve fren temizleyici spreyler',
            ],
            [
                'file' => 'slayt-sanayi-atolye', 'category' => 'Yapıştırma ve Sızdırmazlık',
                'title' => 'Sanayi ve atölye için güçlü çözümler',
                'description' => 'Mum silikon, RTV nötr sıvı conta ve ağır kirlere karşı el temizleme kremi.',
                'button' => 'Ürünleri keşfedin',
                'alt' => 'Dyson Professional mum silikon, RTV sıvı conta, el temizleme kremi ve antifriz',
            ],
        ];

        foreach ($slides as $i => $slide) {
            if (Slide::query()->where('title', $slide['title'])->exists()) {
                continue;
            }

            Slide::query()->create([
                'group' => SlideGroup::Hero,
                'title' => $slide['title'],
                'description' => $slide['description'],
                'button_text' => $slide['button'],
                'button_url' => '/kategori/'.$this->categories[$slide['category']]->slug,
                'image_desktop' => $this->asset("slides/{$slide['file']}-masaustu.webp", "slides/{$slide['file']}-masaustu.webp"),
                'image_mobile' => $this->asset("slides/{$slide['file']}-mobil.webp", "slides/{$slide['file']}-mobil.webp"),
                'image_alt' => $slide['alt'],
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }

    /** Tek marka varken "Markalarımız" şeridi boş görünür; ikinci marka eklenene kadar pasif tutulur. */
    private function homepage(): void
    {
        $home = Page::home();

        if (! $home || Brand::query()->active()->count() > 1) {
            return;
        }

        $toggle = fn (?array $blocks) => collect($blocks ?? [])->map(function (array $block) {
            if ($block['type'] === 'featured_brands') {
                $block['data']['is_active'] = false;
            }

            return $block;
        })->all();

        $home->update(['blocks' => $toggle($home->blocks), 'draft_blocks' => $toggle($home->draft_blocks)]);
    }

    /** Etiket bilgilerinden derlenmiş ürün listesi. */
    private function products(): array
    {
        $list = fn (array $items) => '<ul>'.implode('', array_map(fn ($i) => '<li>'.e($i).'</li>', $items)).'</ul>';

        $adblue = fn (string $size, string $code, string $image, bool $bulk = false) => [
            'name' => "Premium AdBlue {$size}".($bulk ? ' (IBC Tank, Dökme)' : ''),
            'code' => $code,
            'image' => $image,
            'category' => 'AdBlue',
            'size' => $size,
            'packaging' => $bulk ? 'IBC tank (dökme)' : 'Bidon',
            'featured' => $size === '20 L',
            'short' => 'SCR teknolojisine sahip dizel araçlar için, zararlı azot oksit (NOx) gazlarını azaltan ISO 22241-1 uyumlu AdBlue.',
            'description' => '<p>Seçici katalitik indirgeme (SCR) teknolojisine sahip dizel motorlar için egzoz gazı arıtma sıvısıdır. '
                .'Euro IV ve sonrası emisyon standartlarına göre üretilmiş SCR sistemli dizel motorlu taşıtlarda kullanıma uygundur ve Euro 6 emisyon limitlerine uyum sağlar.</p>'
                .'<h3>Önemli uyarılar</h3>'
                .$list([
                    'Sadece SCR teknolojisi bulunan dizel motorlarda kullanılır; dizel yakıt deposuna konulmaz.',
                    'Yakıt, yağ, gres, deterjan ve diğer kirleticilerle karıştırılmamalı ve temas ettirilmemelidir.',
                    'Cilt ve gözle temas ettirmeyin, yutmayın, buharını solumayın. Çocuklardan uzak tutun.',
                    'Doğrudan güneş ışığından koruyun; ambalaj kapağını sıkıca kapalı saklayın.',
                ])
                .'<h3>Raf ömrü — sıcaklık tablosu</h3>'
                .'<table><tr><th>Ortam sıcaklığı</th><th>Raf ömrü</th></tr>'
                .'<tr><td>&lt; 10 °C</td><td>36 ay</td></tr><tr><td>&lt; 25 °C</td><td>18 ay</td></tr>'
                .'<tr><td>&lt; 30 °C</td><td>12 ay</td></tr><tr><td>&lt; 35 °C</td><td>6 ay</td></tr></table>',
            'usage' => '<p>SCR sistemli dizel binek araçlar, otobüs ve minibüsler, kamyon ve TIR\'lar, iş makineleri.</p>',
            'specs' => [
                'Standart' => 'ISO 22241-1 (AUS 32)',
                'Uygun araçlar' => 'SCR sistemli dizel motorlar (Euro IV ve üzeri)',
                'Ambalaj' => $bulk ? "{$size} IBC tank (dökme)" : "{$size} bidon",
            ],
        ];

        $camSuyu = fn (string $size, string $code, string $image) => [
            'name' => "Antifrizli Oto Cam Suyu {$size}",
            'code' => $code,
            'image' => $image,
            'category' => 'Oto Cam Suyu',
            'size' => $size,
            'color' => 'Mavi',
            'packaging' => 'Pet şişe',
            'featured' => $size === '5 L',
            'short' => '-21 °C donma noktasına sahip, saf su ile hazırlanmış, camda iz ve leke bırakmayan antifrizli cam suyu.',
            'description' => '<p>Dört mevsim kullanıma uygun antifrizli oto cam suyudur.</p>'
                .$list([
                    'Camda iz ve leke bırakmadan temizler.',
                    'Toz ve çamur tabakasını kolay temizler.',
                    'Net görüş sağlar.',
                    'Tüm mevsim güvenle kullanılır.',
                ]),
            'specs' => ['Donma noktası' => '-21 °C', 'İçerik' => 'Saf su ile hazırlanmış', 'Hacim' => $size],
        ];

        $antifriz37 = fn (string $color, string $code, string $image) => [
            'name' => "Antifriz -37 °C {$color} 3 L",
            'code' => $code,
            'image' => $image,
            'category' => 'Antifriz',
            'size' => '3 L',
            'color' => $color,
            'packaging' => 'Bidon',
            'featured' => $color === 'Mavi',
            'short' => "-37 °C'ye kadar donma koruması sağlayan, 4 mevsim kullanıma uygun {$color} antifriz.",
            'description' => '<p>Motor soğutma sistemleri için 4 mevsim üstün koruma sağlar.</p>'
                .$list(['Donmaya karşı koruma', 'Aşırı ısınmaya karşı koruma', 'Korozyona karşı koruma']),
            'specs' => ['Donma koruması' => '-37 °C', 'Renk' => $color, 'Hacim' => '3 L'],
        ];

        return [
            $adblue('5 L', 'DP-ADB-5L', 'premium-adblue-5l'),
            $adblue('10 L', 'DP-ADB-10L', 'premium-adblue-10l'),
            $adblue('20 L', 'DP-ADB-20L', 'premium-adblue-20l'),
            $adblue('1000 L', 'DP-ADB-1000L', 'premium-adblue-1000l', bulk: true),
            $antifriz37('Mavi', 'DP-ANF37-M-3L', 'antifriz-37-mavi-3l'),
            $antifriz37('Kırmızı', 'DP-ANF37-K-3L', 'antifriz-37-kirmizi-3l'),
            [
                'name' => 'Antifriz 16 kg',
                'code' => 'DP-ANF-16KG',
                'image' => 'antifriz-16kg',
                'category' => 'Antifriz',
                'size' => '16 kg',
                'packaging' => 'Teneke',
                'short' => 'Soğutma sistemleri için uzun ömürlü koruma sağlayan, 4 mevsim kullanıma uygun antifriz.',
                'description' => '<p>Soğutma sistemleri için uzun ömürlü, 4 mevsim koruma sağlar.</p>'
                    .$list(['Donmaya karşı koruma', 'Aşırı ısınmaya karşı koruma', 'Korozyona karşı koruma']),
                'specs' => ['Net ağırlık' => '16 kg', 'Ambalaj' => 'Teneke'],
            ],
            $camSuyu('1 L', 'DP-CAM-1L', 'oto-cam-suyu-1l'),
            $camSuyu('3 L', 'DP-CAM-3L', 'oto-cam-suyu-3l'),
            $camSuyu('5 L', 'DP-CAM-5L', 'oto-cam-suyu-5l'),
            [
                'name' => '+40 Multi Sprey 200 ml',
                'code' => 'DP-MLT-200',
                'image' => 'multi-sprey-40-200ml',
                'category' => 'Yağlayıcı ve Pas Sökücü Spreyler',
                'size' => '200 ml',
                'packaging' => 'Aerosol',
                'featured' => true,
                'short' => 'Gıcırtıları susturan, nemi uzaklaştıran, temizleyip koruyan çok amaçlı bakım spreyi.',
                'description' => $list(['Gıcırtıları susturur', 'Nemi uzaklaştırır', 'Temizler ve korur', 'Paslanmış bağlantıları çözer', 'Sıkışmış mekanizmaları çalıştırır'])
                    .'<p>Kullanmadan önce ambalaj üzerindeki uyarıları okuyunuz.</p>',
                'specs' => ['Hacim' => '200 ml', 'Ambalaj' => 'Aerosol sprey'],
            ],
            [
                'name' => 'Pas Sökücü Sprey MoS2 400 ml',
                'code' => 'DP-PAS-400',
                'image' => 'pas-sokucu-sprey-400ml',
                'category' => 'Yağlayıcı ve Pas Sökücü Spreyler',
                'size' => '400 ml',
                'packaging' => 'Aerosol',
                'featured' => true,
                'short' => 'MoS2 katkılı; pası gevşeten, sıkışmış parçaları çalıştıran ve korozyona karşı koruyan sprey.',
                'description' => $list(['Pası gevşetir', 'Sıkışmış parçaları çalıştırır', 'Gıcırtıları giderir', 'Korozyona karşı korur', 'Kir ve yağ kalıntılarını temizler'])
                    .'<p>Kullanmadan önce ambalaj üzerindeki uyarıları okuyunuz.</p>',
                'specs' => ['Katkı' => 'MoS2 (molibden disülfür)', 'Hacim' => '400 ml', 'Ambalaj' => 'Aerosol sprey'],
            ],
            [
                'name' => 'Sıvı Gres Sprey 400 ml',
                'code' => 'DP-GRS-400',
                'image' => 'sivi-gres-sprey-400ml',
                'category' => 'Yağlayıcı ve Pas Sökücü Spreyler',
                'size' => '400 ml',
                'packaging' => 'Aerosol',
                'short' => 'Rulman, dişli ve zincirler için üstün yağlama sağlayan, sürtünmeyi azaltan sıvı gres spreyi.',
                'description' => $list(['Üstün yağlama sağlar', 'Sürtünmeyi azaltır', 'Aşınmaya karşı korur', 'Gıcırtıları giderir', 'Uzun süreli koruma sağlar'])
                    .'<p>Kullanmadan önce ambalaj üzerindeki uyarıları okuyunuz.</p>',
                'specs' => ['Hacim' => '400 ml', 'Ambalaj' => 'Aerosol sprey'],
            ],
            [
                'name' => 'Motor Temizleyici Sprey 500 ml',
                'code' => 'DP-MTS-500',
                'image' => 'motor-temizleyici-sprey-500ml',
                'category' => 'Temizleyici Spreyler',
                'size' => '500 ml',
                'packaging' => 'Aerosol',
                'short' => 'Güçlü yağ çözme formülüyle karbon ve zorlu kirleri temizleyen, hızlı buharlaşan motor temizleyici.',
                'description' => $list(['Güçlü yağ çözme formülü', 'Karbon ve zorlu kirleri temizler', 'Hızlı buharlaşır, kalıntı bırakmaz', 'Motor ve metal parçalarda güvenli kullanım'])
                    .'<p>Kullanmadan önce ambalaj üzerindeki uyarıları okuyunuz.</p>',
                'specs' => ['Hacim' => '500 ml', 'Ambalaj' => 'Aerosol sprey'],
            ],
            [
                'name' => 'Fren Balata Genel Temizleyici 500 ml',
                'code' => 'DP-FBT-500',
                'image' => 'fren-balata-temizleyici-500ml',
                'category' => 'Temizleyici Spreyler',
                'size' => '500 ml',
                'packaging' => 'Aerosol',
                'short' => 'Fren balata ve diskleri ile diğer metal yüzeylerde yağ ve lekeleri temizleyen sprey.',
                'description' => $list(['Yağı ve lekeleri temizler', 'Fren balatalarında güvenlidir'])
                    .'<p>Kullanmadan önce ambalaj üzerindeki uyarıları okuyunuz.</p>',
                'specs' => ['Hacim' => '500 ml', 'Ambalaj' => 'Aerosol sprey'],
            ],
            [
                'name' => 'Hot Melt Stick Mum Silikon Ø7,4 mm × 300 mm',
                'code' => 'DYSN2594',
                'image' => 'hot-melt-mum-silikon-200gr',
                'category' => 'Mum Silikon',
                'size' => '200 gr',
                'packaging' => 'Kutu',
                'featured' => true,
                'short' => 'Sıcak silikon tabancaları için 7,4 mm çaplı, 300 mm uzunluğunda mum silikon; yaklaşık 2 dakikada sabitlenir.',
                'description' => $list(['Hızlı ve güçlü yapıştırma', 'Kolay kullanım', 'Ekstra güçlü bağlantı (Extra Strong Bonding)', '2 dakikada sabitlenme']),
                'specs' => ['Çap' => '7,4 mm', 'Uzunluk' => '300 mm', 'Net ağırlık' => '200 gr', 'Sabitlenme süresi' => 'Yaklaşık 2 dakika'],
            ],
            [
                'name' => 'RTV Nötr Sıvı Conta Siyah 200 ml',
                'code' => 'DP-RTV-SYH-200',
                'image' => 'rtv-notr-sivi-conta-siyah-200ml',
                'category' => 'Sıvı Contalar',
                'size' => '200 ml',
                'color' => 'Siyah',
                'packaging' => 'Basınçlı kartuş',
                'featured' => true,
                'short' => '-60 °C / +300 °C sıcaklık dayanımlı, yakıt, yağ ve kimyasallara dayanıklı nötr RTV silikon sıvı conta.',
                'description' => $list(['Yüksek sıcaklık dayanımı', 'Mükemmel sızdırmazlık', 'Korozyona karşı koruma', 'UV dayanımı', 'Yakıt, yağ ve kimyasallara dayanıklı']),
                'usage' => '<p>Otomotiv, endüstriyel kullanım, denizcilik ve genel uygulamalar.</p>',
                'specs' => ['Tip' => 'Nötr RTV silikon', 'Renk' => 'Siyah', 'Sıcaklık dayanımı' => '-60 °C / +300 °C', 'Hacim' => '200 ml'],
            ],
            [
                'name' => 'Çok Amaçlı El Temizleme Kremi 3 kg',
                'code' => 'DP-ELK-3KG',
                'image' => 'el-temizleme-kremi-3kg',
                'category' => 'El Temizleme Ürünleri',
                'size' => '3 kg',
                'packaging' => 'Pompalı kova',
                'featured' => true,
                'short' => 'Yağ, gres, kurum, is, boya, tutkal ve diğer zorlu kirleri cildi kurutmadan derinlemesine temizleyen pompalı el temizleme kremi.',
                'description' => '<p>Ağır kirlere karşı güçlü temizlik sağlayan, ekonomik boy el temizleme kremidir.</p>'
                    .$list([
                        'Güçlü formül: yağ, gres, kurum, is ve en zorlu kirlerde dahi temizlik sağlar.',
                        'Cildi kurutmadan derinlemesine temizler, yumuşaklık sağlar.',
                        'Ahşap tozlu: doğal aşındırıcı partiküllerle etkili ve güvenli temizlik.',
                        'Hoş kokulu, kolay durulanır.',
                    ])
                    .'<h3>Kullanım şekli</h3><p>Kuru ellerinize bir miktar ürün alın, kir çözülene kadar ovun ve su ile iyice durulayın.</p>',
                'usage' => '<p>Sanayi, otomotiv, inşaat, matbaa, atölye ve tüm endüstriyel alanlar.</p>',
                'specs' => ['Net ağırlık' => '3 kg', 'Ambalaj' => 'Pompalı kova', 'İçerik' => 'Doğal aşındırıcı partiküller (ahşap tozu)', 'Menşei' => 'Türkiye'],
            ],
        ];
    }
}
