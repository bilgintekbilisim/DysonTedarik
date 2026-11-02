<?php

namespace Tests\Feature\Frontend;

use App\Models\Page;
use Database\Seeders\CorporateContentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CorporateContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([SettingsSeeder::class, PageSeeder::class, MenuSeeder::class, CorporateContentSeeder::class]);
    }

    #[Test]
    public function the_four_corporate_pages_are_published_with_content(): void
    {
        $this->get('/hakkimizda')->assertOk()->assertSee('Biz kimiz?')->assertSee('Bizimle çalışırken');
        $this->get('/vizyonumuz')->assertOk()->assertSee('çözüm ortağı');
        $this->get('/misyonumuz')->assertOk()->assertSee('Şeffaf teklif');
        $this->get('/kalite-politikamiz')->assertOk()->assertSee('Mevzuata uygunluk');

        // Henüz yazılmamış sayfalar taslak kalır.
        $this->get('/degerlerimiz')->assertNotFound();

        $this->get('/')->assertSee('Sanayi, otomotiv ve atölyeler için tek noktadan tedarik')
            ->assertSee('href="'.url('/hakkimizda').'"', false);
    }

    #[Test]
    public function texts_contain_no_unverified_claims(): void
    {
        $text = Page::query()->corporate()->pluck('content')->implode(' ');

        foreach (['ISO', 'sertifika', 'lider', 'kuruluş', 'yıllık deneyim', 'Alman teknolojisi'] as $claim) {
            $this->assertStringNotContainsStringIgnoringCase($claim, $text, "Doğrulanmamış ifade: {$claim}");
        }
    }

    #[Test]
    public function panel_edits_are_not_overwritten(): void
    {
        Page::query()->where('slug', 'vizyonumuz')->firstOrFail()->update(['content' => '<p>Panelde yazıldı</p>']);

        $this->seed(CorporateContentSeeder::class);

        $this->assertSame('<p>Panelde yazıldı</p>', Page::query()->where('slug', 'vizyonumuz')->value('content'));
    }
}
