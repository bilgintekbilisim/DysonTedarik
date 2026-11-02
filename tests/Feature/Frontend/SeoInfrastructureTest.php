<?php

namespace Tests\Feature\Frontend;

use App\Enums\ContentStatus;
use App\Filament\Pages\ManageSettings;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\User;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeoInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([SettingsSeeder::class, PageSeeder::class, MenuSeeder::class]);
    }

    #[Test]
    public function the_sitemap_lists_only_public_indexable_urls(): void
    {
        Page::query()->where('slug', 'hakkimizda')->update(['status' => ContentStatus::Published]);
        Page::query()->where('slug', 'vizyonumuz')->update(['status' => ContentStatus::Published, 'noindex' => true]);
        $category = Category::factory()->create(['name' => 'Silikonlar']);
        Category::factory()->create(['name' => 'Gizli', 'is_active' => false]);
        Brand::factory()->create(['name' => 'Marka A']);
        Product::factory()->for($category)->create(['name' => 'Görünür Ürün']);
        Product::factory()->inactive()->create(['name' => 'Pasif Ürün']);
        Product::factory()->create(['name' => 'Başka Kanonik', 'canonical_url' => 'https://baska.test/x']);

        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = $response->getContent();

        $this->assertNotFalse(simplexml_load_string($xml), 'Geçerli XML olmalı');
        $this->assertStringContainsString('<loc>'.url('/').'</loc>', $xml);
        $this->assertStringContainsString(url('/hakkimizda'), $xml);
        $this->assertStringContainsString(url('/kategori/silikonlar'), $xml);
        $this->assertStringContainsString(url('/marka/marka-a'), $xml);
        $this->assertStringContainsString(url('/urun/gorunur-urun'), $xml);
        $this->assertStringNotContainsString('vizyonumuz', $xml);
        $this->assertStringNotContainsString('misyonumuz', $xml, 'Taslak sayfa eklenmemeli');
        $this->assertStringNotContainsString('gizli', $xml);
        $this->assertStringNotContainsString('pasif-urun', $xml);
        $this->assertStringNotContainsString('baska-kanonik', $xml);
    }

    #[Test]
    public function the_sitemap_updates_when_content_changes(): void
    {
        $this->get('/sitemap.xml')->assertDontSee('yeni-urun');

        Product::factory()->create(['name' => 'Yeni Ürün']);

        $this->get('/sitemap.xml')->assertSee(url('/urun/yeni-urun'));
    }

    #[Test]
    public function robots_txt_blocks_everything_outside_production(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');

        $this->app['env'] = 'production';

        $this->get('/robots.txt')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.url('/sitemap.xml'))
            ->assertDontSee("Disallow: /\n", false);
    }

    #[Test]
    public function redirects_apply_only_to_missing_urls_and_count_hits(): void
    {
        Redirect::query()->create(['from_path' => '/Eski-Urun/', 'to_url' => '/urunler', 'status_code' => 301]);
        Redirect::query()->create(['from_path' => '/gecici', 'to_url' => 'https://ornek.test/kampanya', 'status_code' => 302]);
        Redirect::query()->create(['from_path' => '/urunler', 'to_url' => '/markalar']);
        Redirect::query()->create(['from_path' => '/pasif', 'to_url' => '/urunler', 'is_active' => false]);

        $this->get('/eski-urun')->assertStatus(301)->assertRedirect(url('/urunler'));
        $this->get('/gecici?x=1')->assertStatus(302)->assertRedirect('https://ornek.test/kampanya');
        $this->get('/urunler')->assertOk(); // canlı sayfa gölgelenmez
        $this->get('/pasif')->assertNotFound();
        $this->post('/eski-urun')->assertStatus(405);

        $this->assertSame(1, Redirect::query()->where('from_path', '/eski-urun')->value('hits'));
    }

    #[Test]
    public function changing_a_published_slug_creates_a_301_from_the_old_address(): void
    {
        $product = Product::factory()->create(['name' => 'Eski Ad']);
        $this->assertSame('eski-ad', $product->slug);

        $product->update(['slug' => 'yeni-ad']);

        $this->get('/urun/eski-ad')->assertStatus(301)->assertRedirect(url('/urun/yeni-ad'));
        $this->get('/urun/yeni-ad')->assertOk();

        // Geri alınırsa döngü oluşmaz ve zincir düzleşir.
        $product->update(['slug' => 'eski-ad']);

        $this->get('/urun/eski-ad')->assertOk();
        $this->get('/urun/yeni-ad')->assertStatus(301)->assertRedirect(url('/urun/eski-ad'));
        $this->assertSame(0, Redirect::query()->where('from_path', '/urun/eski-ad')->count());
    }

    #[Test]
    public function category_and_page_slug_changes_redirect_too(): void
    {
        $category = Category::factory()->create(['name' => 'Köpükler']);
        $category->update(['slug' => 'montaj-kopukleri']);
        $page = Page::query()->where('slug', 'hakkimizda')->firstOrFail();
        $page->update(['slug' => 'biz-kimiz', 'status' => ContentStatus::Published]);

        $this->get('/kategori/kopukler')->assertRedirect(url('/kategori/montaj-kopukleri'));
        $this->get('/hakkimizda')->assertRedirect(url('/biz-kimiz'));
    }

    #[Test]
    public function the_smtp_test_action_reports_the_log_mailer(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageSettings::class)
            ->callAction('testMail')
            ->assertNotified('Test e-postası gönderildi');
    }
}
