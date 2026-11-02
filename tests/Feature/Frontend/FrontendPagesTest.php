<?php

namespace Tests\Feature\Frontend;

use App\Enums\ContentStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FrontendPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([SettingsSeeder::class, PageSeeder::class, MenuSeeder::class]);
    }

    private function block(string $type, array $data): array
    {
        return ['type' => $type, 'data' => ['is_active' => true, 'visibility' => 'all', 'variant' => null, ...$data]];
    }

    #[Test]
    public function the_home_page_renders_published_blocks_and_hides_drafts_from_visitors(): void
    {
        Page::home()->update([
            'blocks' => [
                $this->block('rich_text', ['title' => 'Yayındaki blok', 'content' => '<p>Canlı</p>']),
                $this->block('rich_text', ['title' => 'Pasif blok', 'content' => '<p>x</p>', 'is_active' => false]),
                $this->block('rich_text', ['title' => 'Mobil blok', 'content' => '<p>m</p>', 'visibility' => 'mobile']),
            ],
            'draft_blocks' => [$this->block('rich_text', ['title' => 'Taslak blok', 'content' => '<p>Taslak</p>'])],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Yayındaki blok')
            ->assertDontSee('Pasif blok')
            ->assertDontSee('Taslak blok')
            ->assertSee('lg:hidden', false);

        $this->get('/?onizleme=1')->assertOk()->assertDontSee('Taslak blok');

        $this->actingAs(User::factory()->create())
            ->get('/?onizleme=1')
            ->assertOk()
            ->assertSee('Taslak blok')
            ->assertSee('Önizleme modu')
            ->assertSee('noindex', false);
    }

    #[Test]
    public function dynamic_blocks_without_content_are_skipped(): void
    {
        // Seed'deki ana sayfada slayt, ürün ve marka yok; bu bloklar boş başlıkla çizilmemeli.
        $this->get('/')->assertOk()->assertDontSee('Öne Çıkan Ürünler')->assertDontSee('Markalarımız');

        Product::factory()->featured()->create(['name' => 'Vitrin Ürünü']);

        $this->get('/')->assertSee('Öne Çıkan Ürünler')->assertSee('Vitrin Ürünü');
    }

    #[Test]
    public function corporate_pages_are_visible_only_when_published(): void
    {
        $this->get('/hakkimizda')->assertNotFound();
        $this->get('/ana-sayfa')->assertNotFound();

        Page::query()->where('slug', 'hakkimizda')->update(['status' => ContentStatus::Published, 'content' => '<h2>Biz kimiz</h2>']);

        $this->get('/hakkimizda')
            ->assertOk()
            ->assertSee('<h2>Biz kimiz</h2>', false)
            ->assertSee('"@type":"BreadcrumbList"', false);

        $this->actingAs(User::factory()->create())->get('/vizyonumuz')->assertOk()->assertSee('Önizleme modu');
    }

    #[Test]
    public function the_header_menu_hides_links_to_unpublished_pages(): void
    {
        $this->get('/')->assertDontSee('href="'.url('/hakkimizda').'"', false);

        Page::query()->where('slug', 'hakkimizda')->firstOrFail()->update(['status' => ContentStatus::Published]);

        $this->get('/')->assertSee('href="'.url('/hakkimizda').'"', false);
    }

    #[Test]
    public function category_pages_list_products_with_filters_and_noindex_filtered_views(): void
    {
        $category = Category::factory()->create(['name' => 'Silikonlar', 'description' => '<p>Kategori açıklaması</p>']);
        $brandA = Brand::factory()->create(['name' => 'Marka A']);
        Product::factory()->for($category)->for($brandA)->create(['name' => 'A Ürünü']);
        Product::factory()->for($category)->create(['name' => 'B Ürünü']);

        $this->get('/kategori/silikonlar')
            ->assertOk()
            ->assertSee('A Ürünü')->assertSee('B Ürünü')
            ->assertSee('Kategori açıklaması')
            ->assertSee('index, follow', false);

        $this->get('/kategori/silikonlar?marka[]=marka-a')
            ->assertOk()
            ->assertSee('A Ürünü')->assertDontSee('B Ürünü')
            ->assertSee('noindex, follow', false)
            ->assertSee('<link rel="canonical" href="'.url('/kategori/silikonlar').'">', false);

        $category->update(['is_active' => false]);
        $this->get('/kategori/silikonlar')->assertNotFound();
    }

    #[Test]
    public function product_pages_show_details_and_structured_data(): void
    {
        app(SettingsService::class)->setMany(['contact' => ['whatsapp' => '+90 500 000 00 00']]);

        $product = Product::factory()->for(Brand::factory()->state(['name' => 'Marka X']))->create([
            'name' => 'Nötr Silikon', 'code' => 'NS-1',
            'technical_specs' => [['label' => 'Kür süresi', 'value' => '24 saat']],
            'description' => '<p>Detay metni</p>',
        ]);

        $this->get("/urun/{$product->slug}")
            ->assertOk()
            ->assertSee('Nötr Silikon')->assertSee('NS-1')->assertSee('Kür süresi')->assertSee('Detay metni')
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"sku":"NS-1"', false)
            ->assertSee('https://wa.me/905000000000?text=', false)
            ->assertSee('<meta property="og:type" content="product">', false);
    }

    #[Test]
    public function inactive_and_scheduled_products_are_not_found(): void
    {
        $inactive = Product::factory()->inactive()->create();
        $scheduled = Product::factory()->create(['published_at' => now()->addWeek()]);

        $this->get("/urun/{$inactive->slug}")->assertNotFound();
        $this->get("/urun/{$scheduled->slug}")->assertNotFound();
    }

    #[Test]
    public function user_content_is_escaped(): void
    {
        $product = Product::factory()->create(['name' => '<script>alert(1)</script>Ürün']);

        $this->get("/urun/{$product->slug}")
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);

        $this->get('/urunler?q=<script>x</script>')->assertOk()->assertDontSee('<script>x</script>', false);
    }

    #[Test]
    public function brand_pages_list_their_products(): void
    {
        $brand = Brand::factory()->create(['name' => 'Marka Y']);
        Product::factory()->for($brand)->create(['name' => 'Y Ürünü']);
        Product::factory()->create(['name' => 'Başka Ürün']);

        $this->get('/markalar')->assertOk()->assertSee('Marka Y');
        $this->get("/marka/{$brand->slug}")->assertOk()->assertSee('Y Ürünü')->assertDontSee('Başka Ürün');
    }

    #[Test]
    public function search_suggestions_return_json_and_are_rate_limited(): void
    {
        Product::factory()->create(['name' => 'Montaj Köpüğü', 'code' => 'MK-750']);

        $this->getJson('/arama/oneriler?q=MK-7')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'MK-750')
            ->assertJsonStructure(['data' => [['name', 'code', 'brand', 'url', 'image']], 'all_url']);

        $this->getJson('/arama/oneriler?q=a')->assertOk()->assertJsonCount(0, 'data');

        foreach (range(1, 60) as $i) {
            $this->getJson('/arama/oneriler?q=mk');
        }

        $this->getJson('/arama/oneriler?q=mk')->assertStatus(429);
    }

    #[Test]
    public function seo_tags_use_settings_defaults(): void
    {
        app(SettingsService::class)->setMany([
            'seo' => ['default_description' => 'Varsayılan açıklama'],
            'integrations' => ['google_site_verification' => 'abcdefghij12345'],
        ]);

        $this->get('/urunler')
            ->assertOk()
            ->assertSee('<title>Ürünler | Dyson Tedarik</title>', false)
            ->assertSee('<meta name="description" content="Varsayılan açıklama">', false)
            ->assertSee('<meta name="google-site-verification" content="abcdefghij12345">', false);
    }

    #[Test]
    public function the_contact_page_works_without_a_published_page(): void
    {
        app(SettingsService::class)->setMany(['contact' => ['phone' => '+90 212 000 00 00', 'email' => 'info@ornek.test']]);

        $this->get('/iletisim')->assertOk()->assertSee('+90 212 000 00 00')->assertSee('info@ornek.test');
    }

    #[Test]
    public function unknown_urls_render_the_branded_404_page(): void
    {
        $this->get('/boyle-bir-sayfa-yok')
            ->assertNotFound()
            ->assertSee('Aradığınız sayfa bulunamadı')
            ->assertSee('noindex', false);
    }
}
