<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public static function screens(): array
    {
        return [
            'ürünler' => ['/admin/urunler'],
            'yeni ürün' => ['/admin/urunler/create'],
            'kategoriler' => ['/admin/kategoriler'],
            'yeni kategori' => ['/admin/kategoriler/create'],
            'markalar' => ['/admin/markalar'],
            'yeni marka' => ['/admin/markalar/create'],
            'özellikler' => ['/admin/urun-ozellikleri'],
            'yeni özellik' => ['/admin/urun-ozellikleri/create'],
        ];
    }

    #[Test]
    #[DataProvider('screens')]
    public function editors_can_open_catalog_screens(string $url): void
    {
        Product::factory()->for(Brand::factory())->for(Category::factory())->create();

        $this->get($url)->assertOk();
    }

    #[Test]
    public function edit_screens_open_for_existing_records(): void
    {
        $product = Product::factory()->for(Brand::factory())->for(Category::factory())->create();
        $attribute = ProductAttribute::query()->create(['name' => 'Renk']);

        $this->get("/admin/urunler/{$product->id}/edit")->assertOk()->assertSee($product->code);
        $this->get("/admin/kategoriler/{$product->category_id}/edit")->assertOk();
        $this->get("/admin/markalar/{$product->brand_id}/edit")->assertOk();
        $this->get("/admin/urun-ozellikleri/{$attribute->id}/edit")->assertOk();
    }

    #[Test]
    public function a_product_can_be_created_with_categories_specs_and_filter_values(): void
    {
        $brand = Brand::factory()->create();
        $main = Category::factory()->create(['name' => 'Silikonlar']);
        $extra = Category::factory()->create(['name' => 'Mastikler']);
        $attribute = ProductAttribute::query()->create(['name' => 'Renk']);
        $white = AttributeValue::query()->create(['attribute_id' => $attribute->id, 'value' => 'Beyaz']);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Nötr Silikon 310 ml',
                'code' => 'NS-310',
                'brand_id' => $brand->id,
                'category_id' => $main->id,
                'categories' => [$extra->id],
                'attributeValues' => [$white->id],
                'description' => '<p>Açıklama</p><script>x</script>',
                'technical_specs' => [['label' => 'Kür süresi', 'value' => '24 saat']],
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->where('code', 'NS-310')->firstOrFail();

        $this->assertSame('notr-silikon-310-ml', $product->slug);
        $this->assertSame($main->id, $product->category_id);
        $this->assertEquals([$extra->id], $product->categories->pluck('id')->all());
        $this->assertEquals([$white->id], $product->attributeValues->pluck('id')->all());
        $this->assertSame('<p>Açıklama</p>', $product->description);
        $this->assertSame([['label' => 'Kür süresi', 'value' => '24 saat']], $product->technical_specs);
    }

    #[Test]
    public function product_codes_must_be_unique_and_safe(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['code' => 'ABC-1']);

        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Kopya', 'code' => 'ABC-1', 'category_id' => $category->id])
            ->call('create')
            ->assertHasFormErrors(['code' => 'unique']);

        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Kötü', 'code' => '<b>x</b>', 'category_id' => $category->id])
            ->call('create')
            ->assertHasFormErrors(['code' => 'regex']);
    }

    #[Test]
    public function the_product_table_searches_by_name_code_and_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Akfix Örnek']);
        $byName = Product::factory()->create(['name' => 'Poliüretan Köpük']);
        $byCode = Product::factory()->create(['code' => 'ZX-999']);
        $byBrand = Product::factory()->for($brand)->create();
        $other = Product::factory()->create(['name' => 'Alakasız', 'code' => 'QQ-1']);

        Livewire::test(ListProducts::class)->searchTable('Köpük')->assertCanSeeTableRecords([$byName])->assertCanNotSeeTableRecords([$other]);
        Livewire::test(ListProducts::class)->searchTable('ZX-99')->assertCanSeeTableRecords([$byCode])->assertCanNotSeeTableRecords([$other]);
        Livewire::test(ListProducts::class)->searchTable('Akfix')->assertCanSeeTableRecords([$byBrand])->assertCanNotSeeTableRecords([$other]);
    }

    #[Test]
    public function category_filter_includes_sub_categories(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->childOf($root)->create();
        $inChild = Product::factory()->for($child)->create();
        $elsewhere = Product::factory()->for(Category::factory())->create();

        Livewire::test(ListProducts::class)
            ->filterTable('category', $root->id)
            ->assertCanSeeTableRecords([$inChild])
            ->assertCanNotSeeTableRecords([$elsewhere]);
    }

    #[Test]
    public function a_category_cannot_become_its_own_ancestor(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->childOf($root)->create();
        $grandChild = Category::factory()->childOf($child)->create();

        $options = CategoryForm::parentOptions($root);

        $this->assertArrayNotHasKey($root->id, $options);
        $this->assertArrayNotHasKey($child->id, $options);
        $this->assertArrayNotHasKey($grandChild->id, $options, 'En alt seviye üst kategori olamaz');
    }

    #[Test]
    public function deleting_a_category_moves_its_children_up(): void
    {
        $root = Category::factory()->create();
        $middle = Category::factory()->childOf($root)->create();
        $leaf = Category::factory()->childOf($middle)->create();

        $middle->delete();

        $this->assertSame($root->id, $leaf->refresh()->parent_id);
    }

    #[Test]
    public function the_import_action_accepts_an_uploaded_csv_and_removes_it_afterwards(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent('urunler.csv', "kod;ad;marka\nUP-1;Panelden Gelen;Yeni Marka\n");

        Livewire::test(ListProducts::class)
            ->callAction('import', data: ['file' => $file, 'create_missing' => true])
            ->assertHasNoActionErrors()
            ->assertNotified('Aktarım tamamlandı');

        $this->assertSame('Yeni Marka', Product::query()->where('code', 'UP-1')->firstOrFail()->brand->name);
        $this->assertSame([], Storage::disk('local')->allFiles('imports'));
    }

    #[Test]
    public function the_export_action_downloads_a_file(): void
    {
        Product::factory()->count(3)->create();

        Livewire::test(ListProducts::class)
            ->callAction('export', data: ['format' => 'csv'])
            ->assertFileDownloaded('urunler-'.now()->format('Y-m-d').'.csv');
    }

    #[Test]
    public function editing_keeps_relations_in_sync(): void
    {
        $product = Product::factory()->for(Category::factory())->create();
        $a = Category::factory()->create();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['categories' => [$a->id], 'is_featured' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertTrue($product->is_featured);
        $this->assertEquals([$a->id], $product->categories->pluck('id')->all());
    }
}
