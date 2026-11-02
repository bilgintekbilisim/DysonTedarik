<?php

namespace App\Services\Catalog;

use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Throwable;

/**
 * Excel (.xlsx) veya CSV dosyasından ürün aktarımı. Ürünler "kod" ile eşleşir:
 * kod varsa güncellenir, yoksa oluşturulur. Boş hücre mevcut değeri değiştirmez.
 * Her satır kendi işleminde kaydedilir; hatalı satırlar diğerlerini engellemez.
 */
class ProductImporter
{
    public const MAX_ROWS = 5000;

    private ImportResult $result;

    private bool $createMissing = true;

    /** @var array<string, int> */
    private array $brandCache = [];

    /** @var array<string, int> */
    private array $categoryCache = [];

    public function import(string $path, string $extension, bool $createMissing = true): ImportResult
    {
        $this->result = new ImportResult;
        $this->createMissing = $createMissing;
        $this->brandCache = $this->categoryCache = [];

        @set_time_limit(300);

        $reader = $this->reader($path, strtolower($extension));
        $reader->open($path);

        try {
            $columns = null;
            $rowNumber = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;
                    $cells = array_map(fn ($cell) => $this->cellToString($cell), $row->toArray());

                    if ($columns === null) {
                        $columns = $this->mapHeader($cells);

                        continue;
                    }

                    if (count(array_filter($cells, fn ($c) => $c !== '')) === 0) {
                        continue;
                    }

                    if ($rowNumber - 1 > self::MAX_ROWS) {
                        $this->result->errors[$rowNumber] = 'Satır sınırı aşıldı ('.self::MAX_ROWS.'). Kalan satırlar için dosyayı bölün.';
                        break 2;
                    }

                    $data = [];
                    foreach ($columns as $index => $key) {
                        $data[$key] = $cells[$index] ?? '';
                    }

                    $this->importRow($rowNumber, $data);
                }

                break; // Yalnızca ilk sayfa ürün listesidir.
            }
        } finally {
            $reader->close();
        }

        return $this->result;
    }

    private function reader(string $path, string $extension): ReaderInterface
    {
        return match ($extension) {
            'xlsx' => new XlsxReader,
            'csv', 'txt' => new CsvReader($this->csvOptions($path)),
            default => throw new InvalidArgumentException('Desteklenmeyen dosya türü. .xlsx veya .csv yükleyin.'),
        };
    }

    /** Türkçe Excel CSV'yi ";" ile kaydeder; ilk satıra bakıp ayırıcıyı seçer. */
    private function csvOptions(string $path): CsvOptions
    {
        $firstLine = (string) fgets($handle = fopen($path, 'r'));
        fclose($handle);

        $options = new CsvOptions;
        $options->FIELD_DELIMITER = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        return $options;
    }

    private function cellToString(mixed $cell): string
    {
        return match (true) {
            $cell instanceof \DateTimeInterface => $cell->format('Y-m-d H:i'),
            is_float($cell) && floor($cell) === $cell => (string) (int) $cell,
            is_bool($cell) => $cell ? '1' : '0',
            default => trim((string) $cell),
        };
    }

    /** @return array<int, string> sütun indeksi => sözleşme anahtarı */
    private function mapHeader(array $cells): array
    {
        $columns = [];

        foreach ($cells as $index => $header) {
            $key = ProductSheet::normalizeHeader($header);

            if (array_key_exists($key, ProductSheet::COLUMNS)) {
                $columns[$index] = $key;
            }
        }

        if (! in_array('kod', $columns, true)) {
            throw new InvalidArgumentException('Dosyanın ilk satırında "kod" sütunu bulunamadı. Şablonu indirip başlıkları kontrol edin.');
        }

        return $columns;
    }

    private function importRow(int $rowNumber, array $data): void
    {
        $code = trim($data['kod'] ?? '');
        $product = $code === '' ? null : Product::withTrashed()->where('code', $code)->first();

        $validator = Validator::make($data, [
            'kod' => ['required', 'max:100', 'regex:/^[\pL\pN._\/-]+$/u'],
            'ad' => [$product ? 'nullable' : 'required', 'max:255'],
            'kisa_aciklama' => ['nullable', 'max:500'],
            'seo_baslik' => ['nullable', 'max:70'],
            'seo_aciklama' => ['nullable', 'max:320'],
            'sira' => ['nullable', 'integer', 'min:0'],
            'marka' => ['nullable', 'max:255'],
            'renk' => ['nullable', 'max:255'],
            'hacim' => ['nullable', 'max:255'],
            'ambalaj' => ['nullable', 'max:255'],
        ], [], [
            'kod' => 'kod', 'ad' => 'ad', 'kisa_aciklama' => 'kısa açıklama', 'seo_baslik' => 'SEO başlık',
            'seo_aciklama' => 'SEO açıklama', 'sira' => 'sıra',
        ]);

        foreach (['aktif', 'one_cikan'] as $flag) {
            $validator->after(function ($v) use ($data, $flag) {
                if (($data[$flag] ?? '') !== '' && ProductSheet::parseBool($data[$flag]) === null) {
                    $v->errors()->add($flag, "\"{$flag}\" sütunu evet veya hayır olmalı.");
                }
            });
        }

        if ($validator->fails()) {
            $this->result->errors[$rowNumber] = ($code !== '' ? "[{$code}] " : '').implode(' ', $validator->errors()->all());

            return;
        }

        try {
            DB::transaction(fn () => $this->saveRow($product, $code, $data));
        } catch (Throwable $e) {
            report($e);
            $this->result->errors[$rowNumber] = "[{$code}] ".($e instanceof InvalidArgumentException ? $e->getMessage() : 'Kaydedilemedi: '.Str::limit($e->getMessage(), 150));
        }
    }

    private function saveRow(?Product $product, string $code, array $data): void
    {
        $isNew = $product === null;
        $product ??= new Product(['code' => $code, 'is_active' => true]);

        $attributes = array_filter([
            'name' => $data['ad'] ?? '',
            'short_description' => $data['kisa_aciklama'] ?? '',
            'description' => $data['aciklama'] ?? '',
            'usage_areas' => $data['kullanim_alanlari'] ?? '',
            'color' => $data['renk'] ?? '',
            'volume' => $data['hacim'] ?? '',
            'packaging' => $data['ambalaj'] ?? '',
            'sort_order' => $data['sira'] ?? '',
            'meta_title' => $data['seo_baslik'] ?? '',
            'meta_description' => $data['seo_aciklama'] ?? '',
        ], fn ($value) => $value !== '');

        if (($data['aktif'] ?? '') !== '') {
            $attributes['is_active'] = ProductSheet::parseBool($data['aktif']);
        }

        if (($data['one_cikan'] ?? '') !== '') {
            $attributes['is_featured'] = ProductSheet::parseBool($data['one_cikan']);
        }

        if (($data['teknik_ozellikler'] ?? '') !== '') {
            $attributes['technical_specs'] = ProductSheet::parseSpecs($data['teknik_ozellikler']);
        }

        if (($data['marka'] ?? '') !== '') {
            $attributes['brand_id'] = $this->brandId($data['marka']);
        }

        if (($data['ana_kategori'] ?? '') !== '') {
            $attributes['category_id'] = $this->categoryId($data['ana_kategori']);
        }

        // Çöp kutusundaki bir ürünün kodu yeniden aktarılırsa ürün geri getirilir (kod benzersiz olduğundan).
        if ($product->trashed()) {
            $product->deleted_at = null;
        }

        $product->fill($attributes)->save();

        if (($data['ek_kategoriler'] ?? '') !== '') {
            $product->categories()->sync(array_map(
                fn (string $path) => $this->categoryId($path),
                ProductSheet::splitList($data['ek_kategoriler']),
            ));
        }

        if (($data['filtre_ozellikleri'] ?? '') !== '') {
            $product->attributeValues()->sync(array_map(
                fn (array $pair) => $this->attributeValueId($pair['attribute'], $pair['value']),
                ProductSheet::parseAttributes($data['filtre_ozellikleri']),
            ));
        }

        $isNew ? $this->result->created++ : $this->result->updated++;
    }

    private function brandId(string $name): int
    {
        $key = Str::lower(trim($name));

        if (isset($this->brandCache[$key])) {
            return $this->brandCache[$key];
        }

        $brand = Brand::withTrashed()->whereRaw('LOWER(name) = ?', [$key])->first()
            ?? Brand::withTrashed()->where('slug', Str::slug($name, '-', 'tr'))->first();

        if (! $brand) {
            $this->guardCreate("Marka bulunamadı: {$name}");
            $brand = Brand::query()->create(['name' => trim($name), 'is_active' => true]);
            $this->result->createdBrands++;
        }

        return $this->brandCache[$key] = $brand->id;
    }

    private function categoryId(string $path): int
    {
        $parts = ProductSheet::splitPath($path);

        if ($parts === []) {
            throw new InvalidArgumentException("Geçersiz kategori yolu: {$path}");
        }

        $parentId = null;
        $cacheKey = '';

        foreach ($parts as $name) {
            $cacheKey .= '>'.Str::lower($name);

            if (isset($this->categoryCache[$cacheKey])) {
                $parentId = $this->categoryCache[$cacheKey];

                continue;
            }

            $category = Category::query()
                ->where('parent_id', $parentId)
                ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                ->first();

            // Tek parçalı yol verilmişse ("Silikonlar") ağacın herhangi bir yerindeki eşsiz adla eşleşmeye izin ver.
            if (! $category && count($parts) === 1) {
                $matches = Category::query()->whereRaw('LOWER(name) = ?', [Str::lower($name)])->limit(2)->get();
                $category = $matches->count() === 1 ? $matches->first() : null;
            }

            if (! $category) {
                $this->guardCreate("Kategori bulunamadı: {$path}");
                $category = Category::query()->create(['name' => $name, 'parent_id' => $parentId, 'is_active' => true]);
                $this->result->createdCategories++;
            }

            $parentId = $this->categoryCache[$cacheKey] = $category->id;
        }

        return $parentId;
    }

    private function attributeValueId(string $attributeName, string $value): int
    {
        $attribute = ProductAttribute::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($attributeName)])
            ->orWhere('slug', Str::slug($attributeName, '-', 'tr'))
            ->first();

        if (! $attribute) {
            $this->guardCreate("Özellik bulunamadı: {$attributeName}");
            $attribute = ProductAttribute::query()->create(['name' => $attributeName, 'is_filterable' => true]);
        }

        return AttributeValue::query()->firstOrCreate(
            ['attribute_id' => $attribute->id, 'slug' => Str::slug($value, '-', 'tr')],
            ['value' => $value],
        )->id;
    }

    private function guardCreate(string $message): void
    {
        if (! $this->createMissing) {
            throw new InvalidArgumentException($message.' ("Eksik marka/kategori/özellikleri oluştur" seçeneği kapalı).');
        }
    }
}
