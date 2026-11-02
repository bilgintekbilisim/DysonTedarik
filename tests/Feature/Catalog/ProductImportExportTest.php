<?php

namespace Tests\Feature\Catalog;

use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Services\Catalog\ProductExporter;
use App\Services\Catalog\ProductImporter;
use App\Services\Catalog\ProductSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductImportExportTest extends TestCase
{
    use RefreshDatabase;

    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imp').'.csv';
        file_put_contents($path, "\xEF\xBB\xBF".$content);

        return $this->tempFiles[] = $path;
    }

    private function xlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $this->tempFiles[] = $path;
    }

    private function importer(): ProductImporter
    {
        return app(ProductImporter::class);
    }

    #[Test]
    public function it_creates_products_with_brand_category_path_specs_and_filters_from_semicolon_csv(): void
    {
        $file = $this->csv(
            "kod;ad;marka;ana_kategori;ek_kategoriler;teknik_ozellikler;filtre_ozellikleri;aktif;one_cikan\n"
            ."NS-310;Nötr Silikon;Örnek Marka;Yapı Kimyasalları > Silikonlar;Mastikler;Kür süresi: 24 saat | Isı: -40/+120 °C;Renk=Beyaz | Hacim=310 ml;evet;evet\n"
        );

        $result = $this->importer()->import($file, 'csv');

        $this->assertSame([], $result->errors);
        $this->assertSame(1, $result->created);
        $this->assertSame(1, $result->createdBrands);
        $this->assertSame(3, $result->createdCategories);

        $product = Product::query()->where('code', 'NS-310')->firstOrFail();
        $this->assertSame('Örnek Marka', $product->brand->name);
        $this->assertSame('Yapı Kimyasalları › Silikonlar', $product->category->fullName());
        $this->assertSame(['Mastikler'], $product->categories->pluck('name')->all());
        $this->assertSame([['label' => 'Kür süresi', 'value' => '24 saat'], ['label' => 'Isı', 'value' => '-40/+120 °C']], $product->technical_specs);
        $this->assertEqualsCanonicalizing(['Beyaz', '310 ml'], $product->attributeValues->pluck('value')->all());
        $this->assertTrue($product->is_featured);
    }

    #[Test]
    public function blank_cells_do_not_overwrite_existing_values(): void
    {
        $product = Product::factory()->for(Brand::factory()->state(['name' => 'Eski Marka']))->create([
            'code' => 'K-1', 'name' => 'Eski Ad', 'short_description' => 'Korunmalı', 'is_active' => true,
        ]);

        $result = $this->importer()->import($this->csv("kod,ad,kisa_aciklama,marka,aktif\nK-1,Yeni Ad,,,hayır\n"), 'csv');

        $this->assertSame(1, $result->updated);
        $product->refresh();
        $this->assertSame('Yeni Ad', $product->name);
        $this->assertSame('Korunmalı', $product->short_description);
        $this->assertSame('Eski Marka', $product->brand->name);
        $this->assertFalse($product->is_active);
    }

    #[Test]
    public function invalid_rows_are_reported_and_valid_rows_still_import(): void
    {
        $file = $this->xlsx([
            ['Kod', 'Ad', 'Aktif'],
            ['OK-1', 'Geçerli', 'evet'],
            ['', 'Kodsuz', ''],
            ['BAD-1', '', ''],
            ['OK-2', 'Bayrak hatası', 'belki'],
            ['<x>', 'Kötü kod', ''],
        ]);

        $result = $this->importer()->import($file, 'xlsx');

        $this->assertSame(1, $result->created);
        $this->assertEqualsCanonicalizing([3, 4, 5, 6], array_keys($result->errors));
        $this->assertSame(['OK-1'], Product::query()->pluck('code')->all());
    }

    #[Test]
    public function missing_brands_and_categories_are_errors_when_creation_is_disabled(): void
    {
        $result = $this->importer()->import($this->csv("kod,ad,marka\nX-1,Ürün,Bilinmeyen\n"), 'csv', createMissing: false);

        $this->assertSame(0, $result->created);
        $this->assertStringContainsString('Marka bulunamadı', $result->errors[2]);
        $this->assertSame(0, Brand::query()->count());
    }

    #[Test]
    public function a_file_without_code_column_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->importer()->import($this->csv("ad,marka\nÜrün,Marka\n"), 'csv');
    }

    #[Test]
    public function unsafe_html_in_descriptions_is_cleaned(): void
    {
        $this->importer()->import($this->csv("kod,ad,aciklama\nH-1,Ürün,\"<p>Metin</p><script>alert(1)</script>\"\n"), 'csv');

        $this->assertSame('<p>Metin</p>', Product::query()->where('code', 'H-1')->value('description'));
    }

    #[Test]
    public function exported_files_can_be_imported_back_without_changes(): void
    {
        $root = Category::factory()->create(['name' => 'Yapı Kimyasalları']);
        $child = Category::factory()->childOf($root)->create(['name' => 'Silikonlar']);
        $attribute = ProductAttribute::query()->create(['name' => 'Renk']);
        $value = AttributeValue::query()->create(['attribute_id' => $attribute->id, 'value' => 'Gri']);
        $product = Product::factory()->for(Brand::factory()->state(['name' => 'Marka A']))->for($child)->create([
            'code' => 'RT-1', 'technical_specs' => [['label' => 'Yoğunluk', 'value' => '1,5 g/ml']],
        ]);
        $product->attributeValues()->attach($value);

        $path = app(ProductExporter::class)->export(Product::query(), 'xlsx');
        $this->tempFiles[] = $path;

        $reader = new Reader;
        $reader->open($path);
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
            break;
        }
        $reader->close();

        $this->assertSame(array_keys(ProductSheet::COLUMNS), $rows[0]);
        $this->assertSame('Yapı Kimyasalları > Silikonlar', $rows[1][3]);
        $this->assertSame('Renk=Gri', $rows[1][12]);

        $result = $this->importer()->import($path, 'xlsx', createMissing: false);

        $this->assertSame([], $result->errors);
        $this->assertSame(1, $result->updated);
        $this->assertSame(0, $result->createdCategories);
        $product->refresh();
        $this->assertSame($child->id, $product->category_id);
        $this->assertSame([['label' => 'Yoğunluk', 'value' => '1,5 g/ml']], $product->technical_specs);
    }

    #[Test]
    public function the_template_has_headers_and_a_help_sheet(): void
    {
        $path = app(ProductExporter::class)->template('xlsx');
        $this->tempFiles[] = $path;

        $reader = new Reader;
        $reader->open($path);
        $names = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $names[] = $sheet->getName();
        }
        $reader->close();

        $this->assertSame(['Ürünler', 'Açıklamalar'], $names);
    }
}
