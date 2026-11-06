<?php

namespace Tests\Feature\Catalog;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Support\VariantTable;
use Database\Seeders\KarbosanCatalogSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KarbosanCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
        $this->seed([SettingsSeeder::class, PageSeeder::class, MenuSeeder::class, KarbosanCatalogSeeder::class]);
    }

    #[Test]
    public function it_creates_the_karbosan_tree_with_all_groups_and_series(): void
    {
        $root = Category::query()->where('name', 'Karbosan')->whereNull('parent_id')->firstOrFail();

        $this->assertSame(21, $root->children()->count());
        $this->assertSame(203, Product::query()->whereIn('category_id', Category::descendantIdsOf($root->id))->count());
        $this->assertSame(203, Product::query()->whereHas('brand', fn ($q) => $q->where('name', 'Karbosan'))->count());

        // Kaynak sitedeki bozuk kodlama düzeltilmiş olmalı.
        $this->assertFalse(Product::query()->where('name', 'like', '%Ã%')->orWhere('description', 'like', '%Ã%')->exists());

        // Her serinin bir görseli var ve dosya yükleme klasörüne kopyalandı.
        $this->assertSame(0, Product::query()->doesntHave('images')->count());
        $path = Product::query()->where('name', 'Metal Kesme Diskleri')->firstOrFail()->images()->value('path');
        Storage::disk('public')->assertExists($path);

        // Tekrar çalıştırma kopya oluşturmaz.
        $this->seed(KarbosanCatalogSeeder::class);
        $this->assertSame(203, Product::query()->count());
        $this->assertSame(203, ProductImage::query()->count());
    }

    #[Test]
    public function an_existing_series_without_an_image_gets_one_but_admin_edits_are_kept(): void
    {
        $product = Product::query()->where('name', 'Metal Kesme Diskleri')->firstOrFail();
        $product->images()->delete();
        $product->update(['short_description' => 'Panelde düzenlendi']);

        $this->seed(KarbosanCatalogSeeder::class);

        $this->assertSame(1, $product->images()->count());
        $this->assertSame('Panelde düzenlendi', $product->refresh()->short_description);
    }

    #[Test]
    public function the_product_page_lists_sizes_and_artikel_numbers_are_searchable(): void
    {
        $product = Product::query()->where('name', 'Metal Kesme Diskleri')->firstOrFail();
        $artikel = collect($product->variant_table['rows'])->pluck(3)->first(fn ($a) => ctype_digit((string) $a));

        $this->get("/urun/{$product->slug}")
            ->assertOk()
            ->assertSee('Ebatlar ve artikeller ('.count($product->variant_table['rows']).')')
            ->assertSee($artikel);

        $this->getJson('/arama/oneriler?q='.$artikel)->assertJsonPath('data.0.code', $product->code);
    }

    #[Test]
    public function a_size_row_prefills_the_quote_form(): void
    {
        $product = Product::query()->where('name', 'Metal Kesme Diskleri')->firstOrFail();
        $row = $product->variant_table['rows'][0];

        $this->get("/teklif-iste?urun={$product->code}&secenek=0")
            ->assertOk()
            ->assertSee("Metal Kesme Diskleri — {$row[0]}", false)
            ->assertSee('Artikel '.$row[3], false);
    }

    #[Test]
    public function the_category_page_offers_karbosan_filters(): void
    {
        $this->get('/kategori/kesme-diskleri')
            ->assertOk()
            ->assertSee('Ürün Tipi')
            ->assertSee('Uygulama Alanı')
            ->assertSee('name="ozellik[urun-tipi][]"', false);

        $this->get('/kategori/kesme-diskleri?ozellik[urun-tipi][]=t42')->assertOk()->assertSee('Metal Kesme Diskleri');
    }

    #[Test]
    public function the_size_table_round_trips_through_the_admin_text_field(): void
    {
        $text = "Tip\tEbat\tArtikel\nT41\t115 x 1.0 x 22.23\t910380\nT42 | 125 x 1.0 | 910390";
        $table = VariantTable::fromText($text);

        $this->assertSame(['Tip', 'Ebat', 'Artikel'], $table['headers']);
        $this->assertSame(['T42', '125 x 1.0', '910390'], $table['rows'][1]);
        $this->assertSame($table, VariantTable::fromText(VariantTable::toText($table)));
        $this->assertNull(VariantTable::fromText('Yalnızca başlık'));

        $this->actingAs(User::factory()->create());
        $product = Product::query()->where('name', 'Metal Kesme Diskleri')->firstOrFail();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['variant_table' => "Ebat | Artikel\n100 x 1 | 123456"])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['headers' => ['Ebat', 'Artikel'], 'rows' => [['100 x 1', '123456']]], $product->refresh()->variant_table);
    }
}
