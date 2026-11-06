<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSitemap;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Filament\Resources\Media\Pages\ManageMedia;
use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Media;
use App\Models\Product;
use App\Models\User;
use App\Support\SearchText;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingsSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchMediaSitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
        $this->seed([SettingsSeeder::class, PageSeeder::class, MenuSeeder::class]);
    }

    public static function searches(): array
    {
        return [
            'büyük harf' => ['METAL KESME'],
            'kelime sırası' => ['kesme metal'],
            'boşluksuz' => ['metalkesme'],
            'tireli' => ['metal-kesme'],
            'Türkçe karakter yok' => ['metal kesme diskleri cok amacli'],
            'yazım hatası' => ['mteal kesme'],
            'kod parçası' => ['krb-metal'],
            'marka ile' => ['karbosan metal'],
            'artikel' => ['910020'],
        ];
    }

    #[Test]
    #[DataProvider('searches')]
    public function product_search_tolerates_case_spaces_order_and_typos(string $term): void
    {
        $brand = Brand::factory()->create(['name' => 'Karbosan']);
        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'name' => 'Metal Kesme Diskleri Çok Amaçlı',
            'code' => 'KRB-METAL-KESME',
            'variant_table' => ['headers' => ['Ebat', 'Artikel'], 'rows' => [['115 x 2.5', '910020']]],
            'is_active' => true,
        ]);
        Product::factory()->create(['name' => 'Premium AdBlue 10 L', 'code' => 'DP-ADB-10L', 'is_active' => true]);

        $this->assertSame([$product->id], Product::query()->active()->searchOrSimilar($term)->pluck('products.id')->all(), $term);
        $this->get('/urunler?q='.urlencode($term))->assertOk()->assertSee('Metal Kesme Diskleri')->assertDontSee('Premium AdBlue');
        $this->getJson('/arama/oneriler?q='.urlencode($term))->assertJsonPath('data.0.code', 'KRB-METAL-KESME');
    }

    #[Test]
    public function unrelated_terms_find_nothing(): void
    {
        Product::factory()->create(['name' => 'Antifriz 16 kg', 'is_active' => true]);

        $this->assertSame(1, Product::query()->searchOrSimilar('antfiriz')->count());
        $this->assertSame(0, Product::query()->searchOrSimilar('xqzw')->count());
        $this->assertNull(SearchText::fuzzyScore(['kesme'], ['antifriz', '16', 'kg']));
    }

    #[Test]
    public function the_brand_filter_lists_brands_of_the_search_results(): void
    {
        $dyson = Brand::factory()->create(['name' => 'Dyson Professional']);
        $karbosan = Brand::factory()->create(['name' => 'Karbosan']);
        Product::factory()->create(['brand_id' => $dyson->id, 'name' => 'Premium AdBlue 10 L', 'is_active' => true]);
        Product::factory()->create(['brand_id' => $karbosan->id, 'name' => 'Metal Kesme Diskleri', 'is_active' => true]);

        $this->get('/urunler')->assertSee('value="'.$dyson->slug.'"', false)->assertSee('value="'.$karbosan->slug.'"', false);
        $this->get('/urunler?q=adblue')->assertSee('value="'.$dyson->slug.'"', false)->assertDontSee('value="'.$karbosan->slug.'"', false);
        $this->get('/urunler?marka[]='.$karbosan->slug)->assertSee('Metal Kesme Diskleri')->assertDontSee('Premium AdBlue');
    }

    #[Test]
    public function the_media_library_lists_every_uploaded_file_and_deleting_removes_it_everywhere(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $disk = Storage::disk('public');
        $disk->put('products/karbosan/disk.webp', 'x');
        $disk->put('products/karbosan/disk-480.webp', 'x');
        $disk->put('catalogs/katalog.pdf', '%PDF-1.4');
        $disk->put('brands/logo.webp', 'x');

        $product = Product::factory()->create();
        $image = $product->images()->create(['path' => 'products/karbosan/disk.webp', 'sort_order' => 0]);
        $catalog = Catalog::query()->create(['title' => 'Katalog', 'file' => 'catalogs/katalog.pdf']);
        $brand = Brand::factory()->create(['logo' => 'brands/logo.webp']);

        Livewire::test(ManageMedia::class)->assertOk();

        // Küçük görsel (-480) ayrı listelenmez.
        $this->assertEqualsCanonicalizing(['products/karbosan/disk.webp', 'catalogs/katalog.pdf', 'brands/logo.webp'], Media::query()->pluck('path')->all());
        $this->assertSame('urun', Media::query()->where('path', 'products/karbosan/disk.webp')->value('folder'));

        $media = Media::query()->where('path', 'products/karbosan/disk.webp')->sole();
        Livewire::test(ManageMedia::class)
            ->assertActionVisible(TestAction::make(DeleteAction::class)->table($media))
            ->callAction(TestAction::make(DeleteAction::class)->table($media));

        $disk->assertMissing(['products/karbosan/disk.webp', 'products/karbosan/disk-480.webp']);
        $this->assertModelMissing($image);

        Media::query()->where('path', 'catalogs/katalog.pdf')->sole()->delete();
        $disk->assertMissing('catalogs/katalog.pdf');
        $this->assertNull(Catalog::withTrashed()->find($catalog->id));

        Media::query()->where('path', 'brands/logo.webp')->sole()->delete();
        $this->assertNull($brand->refresh()->logo);

        // Diskten silinmiş dosyanın kaydı taramada kaldırılır.
        $disk->put('pages/kapak.webp', 'x');
        Livewire::test(ManageMedia::class);
        $disk->delete('pages/kapak.webp');
        Livewire::test(ManageMedia::class)->callAction('sync');
        $this->assertFalse(Media::query()->where('path', 'pages/kapak.webp')->exists());
    }

    #[Test]
    public function the_sitemap_page_summarises_and_regenerates_the_sitemap(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Product::factory()->count(3)->create(['is_active' => true]);

        Livewire::test(ManageSitemap::class)
            ->assertOk()
            ->assertSee('Toplam adres')
            ->assertSee('Ürün')
            ->callAction('regenerate')
            ->assertNotified();

        $this->get('/sitemap.xml')->assertOk()->assertSee('/urun/', false);
        $this->get('/admin/sitemap')->assertOk();
    }

    #[Test]
    public function a_brand_can_be_deleted_and_restored_from_the_list(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $brand = Brand::factory()->create(['name' => 'Silinecek Marka']);

        Livewire::test(ListBrands::class)->callAction(TestAction::make(DeleteAction::class)->table($brand));

        $this->assertSoftDeleted($brand);
        $this->get('/markalar')->assertDontSee('Silinecek Marka');
    }
}
