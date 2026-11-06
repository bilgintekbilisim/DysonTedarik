<?php

namespace Tests\Feature;

use App\Filament\Resources\Catalogs\Pages\CreateCatalog;
use App\Filament\Widgets\InquiriesChart;
use App\Filament\Widgets\LatestContactMessages;
use App\Filament\Widgets\LatestQuoteRequests;
use App\Filament\Widgets\SiteOverview;
use App\Models\Catalog;
use App\Models\QuoteRequest;
use App\Models\User;
use Database\Seeders\ECatalogSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ECatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
        $this->seed([SettingsSeeder::class, PageSeeder::class, MenuSeeder::class]);
    }

    #[Test]
    public function the_sample_catalog_is_listed_with_view_and_download_links(): void
    {
        $this->seed(ECatalogSeeder::class);
        $catalog = Catalog::query()->sole();

        Storage::disk('public')->assertExists($catalog->file);
        $this->assertGreaterThan(100_000, $catalog->file_size);
        $this->assertStringStartsWith('%PDF-', Storage::disk('public')->get($catalog->file));

        $this->get('/e-katalog')
            ->assertOk()
            ->assertSee('Dyson Professional Ürün Kataloğu')
            ->assertSee($catalog->fileUrl(), false)
            ->assertSee('download="dyson-professional-urun-katalogu.pdf"', false);

        // Menüde ve sitemap'te.
        $this->get('/')->assertSee(route('catalogs.index'), false);
        $this->get('/sitemap.xml')->assertSee(route('catalogs.index'), false);

        // Tekrar çalıştırma kopya oluşturmaz.
        $this->seed(ECatalogSeeder::class);
        $this->assertSame(1, Catalog::query()->count());
    }

    #[Test]
    public function inactive_catalogs_are_hidden(): void
    {
        Catalog::query()->create(['title' => 'Eski broşür', 'file' => 'catalogs/eski.pdf', 'is_active' => false]);

        $this->get('/e-katalog')->assertOk()->assertDontSee('Eski broşür')->assertSee('Henüz katalog eklenmedi.');
    }

    #[Test]
    public function an_editor_can_upload_a_catalog_from_the_panel(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateCatalog::class)
            ->fillForm([
                'title' => 'Karbosan Genel Katalog',
                'file' => UploadedFile::fake()->create('katalog.pdf', 300, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $catalog = Catalog::query()->sole();
        $this->assertSame('karbosan-genel-katalog', $catalog->slug);
        Storage::disk('public')->assertExists($catalog->file);
    }

    #[Test]
    public function the_dashboard_shows_the_overview_and_latest_inquiries(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Kontrol Paneli')
            ->assertSee('Hızlı işlemler')
            ->assertSee('Siteyi görüntüle');

        $this->get('/admin/e-kataloglar')->assertOk();

        QuoteRequest::query()->create(['full_name' => 'Ayşe Yılmaz', 'email' => 'ayse@example.com', 'phone' => '05550000000', 'product_name' => 'Metal Kesme Diskleri']);

        Livewire::test(SiteOverview::class)->assertOk()->assertSee('Yeni teklif talepleri');
        Livewire::test(InquiriesChart::class)->assertOk();
        Livewire::test(LatestQuoteRequests::class)->assertOk()->assertSee('Ayşe Yılmaz');
        Livewire::test(LatestContactMessages::class)->assertOk()->assertSee('Henüz mesaj yok');
    }
}
