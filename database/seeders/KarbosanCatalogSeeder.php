<?php

namespace Database\Seeders;

use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Karbosan ürün grupları ve serileri (data/karbosan.json).
 * Veri karbosan.com ürün sayfalarındaki olgusal bilgilerden derlenmiştir: ürün adı, grup, ürün tipi,
 * uygulama alanı/metodu, sektör ve ebat/artikel tablosu. Açıklamalar bu bilgilerden üretilir;
 * üreticinin tanıtım metinleri kopyalanmaz. Görseller eklenmez (kullanım izni panelden yüklenerek verilir).
 * Tekrar çalıştırılabilir; mevcut ürünlere (koda göre) dokunmaz.
 */
class KarbosanCatalogSeeder extends Seeder
{
    /** Grup adı => tekil ürün tanımı (açıklama cümlesinde kullanılır). */
    private const SINGULAR = [
        'Kesme Diskleri' => 'kesme diski',
        'Taşlama Diskleri' => 'taşlama diski',
        'Taşlama Taşları' => 'taşlama taşı',
        'Testere Taşları' => 'testere bileme taşı',
        'Takım Bileme Taşları' => 'takım bileme taşı',
        'Bant Zımparalar' => 'bant zımpara',
        'Rulo Zımparalar' => 'rulo zımpara',
        'Flap Diskler' => 'flap disk',
        'Fiber Diskler' => 'fiber disk',
        'Cırtlı Diskler' => 'cırtlı disk',
        'Vidalı Diskler' => 'vidalı disk',
    ];

    /** @var array<string, ProductAttribute> */
    private array $attributes = [];

    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/data/karbosan.json'), true, flags: JSON_THROW_ON_ERROR);

        $brand = Brand::withTrashed()->firstOrCreate(['name' => 'Karbosan'], [
            'description' => '<p>Karbosan; kesme ve taşlama diskleri, taşlama taşları, zımparalar ve aşındırıcı ürünler üretir. '
                .'Dyson Tedarik olarak Karbosan ürün gruplarının tedarikini yapıyoruz.</p>',
            'website' => 'https://www.karbosan.com',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $root = Category::withTrashed()->firstOrCreate(['name' => 'Karbosan', 'parent_id' => null], [
            'summary' => 'Kesme ve taşlama diskleri, taşlama taşları, bant ve rulo zımparalar, flap, fiber, cırtlı ve vidalı diskler.',
            'description' => '<p>Karbosan ürün grupları: kesme diskleri, taşlama diskleri, taşlama taşları, testere ve takım bileme taşları, '
                .'bant ve rulo zımparalar, flap diskler, fiber diskler, cırtlı diskler ve vidalı diskler. '
                .'Her ürün sayfasında ebat, artikel ve ambalaj bilgileri yer alır; ihtiyacınız olan ebat için teklif isteyebilirsiniz.</p>',
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 5,
        ]);

        foreach ($data as $groupIndex => $group) {
            $category = Category::withTrashed()->firstOrCreate(
                ['name' => $group['group'], 'parent_id' => $root->id],
                ['is_active' => true, 'show_in_menu' => true, 'sort_order' => $groupIndex + 1],
            );

            foreach ($group['products'] as $index => $item) {
                $this->product($item, $group['group'], $category, $brand, $index);
            }
        }
    }

    private function product(array $item, string $group, Category $category, Brand $brand, int $index): void
    {
        $code = 'KRB-'.Str::upper(Str::limit(Str::slug($item['name'], '-', 'tr'), 90, ''));

        if (Product::withTrashed()->where('code', $code)->exists()) {
            return;
        }

        $areas = $item['areas'];
        $singular = self::SINGULAR[$group] ?? self::lower($group);
        $table = ! empty($item['table']['rows']) ? $item['table'] : null;

        $short = 'Karbosan '.$singular.'.'
            .($areas ? ' Uygulama alanları: '.$this->join(array_slice($areas, 0, 5)).(count($areas) > 5 ? ' ve diğerleri' : '').'.' : '');

        $product = Product::query()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => $item['name'],
            'code' => $code,
            'short_description' => Str::limit($short, 495),
            'description' => $this->description($item, $group, $table),
            'technical_specs' => array_values(array_filter([
                $item['types'] ? ['label' => 'Ürün tipi', 'value' => implode(', ', $item['types'])] : null,
                $item['methods'] ? ['label' => 'Uygulama metodu', 'value' => implode(', ', $item['methods'])] : null,
                $item['sectors'] ? ['label' => 'Sektör', 'value' => implode(', ', $item['sectors'])] : null,
                $table ? ['label' => 'Ebat seçeneği', 'value' => count($table['rows']).' farklı ebat'] : null,
                ['label' => 'Üretici', 'value' => 'Karbosan'],
            ])),
            'variant_table' => $table,
            'is_active' => true,
            'sort_order' => $index + 1,
        ]);

        $values = [];
        foreach (['Ürün Tipi' => $item['types'], 'Uygulama Alanı' => $areas, 'Uygulama Metodu' => $item['methods'], 'Sektör' => $item['sectors']] as $attribute => $list) {
            foreach ($list as $value) {
                $values[] = $this->attributeValue($attribute, $value);
            }
        }

        $product->attributeValues()->sync(array_unique($values));
    }

    private function description(array $item, string $group, ?array $table): string
    {
        $list = fn (array $items) => '<ul>'.implode('', array_map(fn ($i) => '<li>'.e($i).'</li>', $items)).'</ul>';

        $html = '<p>'.e($item['name']).', Karbosan\'ın '.e(self::lower($group)).' ürün grubunda yer alır.</p>';
        $html .= $item['areas'] ? '<h3>Uygulama alanları</h3>'.$list($item['areas']) : '';
        $html .= $item['methods'] ? '<h3>Uygulama metodu</h3>'.$list($item['methods']) : '';
        $html .= $item['types'] ? '<h3>Ürün tipi</h3><p>'.e(implode(', ', $item['types'])).'</p>' : '';
        $html .= $item['sectors'] ? '<h3>Kullanıldığı sektörler</h3><p>'.e(implode(', ', $item['sectors'])).'</p>' : '';
        $html .= $table
            ? '<p>Ebat, artikel ve ambalaj bilgileri için <strong>Ebatlar ve artikeller</strong> sekmesine bakın; ihtiyacınız olan ebat için satırdaki "Teklif iste" bağlantısını kullanabilirsiniz.</p>'
            : '<p>Ebat ve ambalaj seçenekleri için teklif isteyin.</p>';

        return $html;
    }

    private function attributeValue(string $attribute, string $value): int
    {
        $attr = $this->attributes[$attribute] ??= ProductAttribute::query()->firstOrCreate(
            ['slug' => Str::slug($attribute, '-', 'tr')],
            ['name' => $attribute, 'is_filterable' => true, 'sort_order' => 10 + count($this->attributes)],
        );

        return AttributeValue::query()->firstOrCreate(
            ['attribute_id' => $attr->id, 'slug' => Str::slug($value, '-', 'tr')],
            ['value' => $value],
        )->id;
    }

    private function join(array $items): string
    {
        $items = array_map(fn ($i) => self::lower($i), $items);

        return count($items) > 1 ? implode(', ', array_slice($items, 0, -1)).' ve '.end($items) : ($items[0] ?? '');
    }

    /** Türkçe küçük harf: I → ı, İ → i (mb_strtolower bunu yapmaz). */
    private static function lower(string $value): string
    {
        return mb_strtolower(strtr($value, ['I' => 'ı', 'İ' => 'i']));
    }
}
