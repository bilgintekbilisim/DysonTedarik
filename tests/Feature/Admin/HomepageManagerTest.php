<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\ManageHomepage;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomepageManagerTest extends TestCase
{
    use RefreshDatabase;

    private Page $home;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PageSeeder::class);
        $this->home = Page::home();
        $this->actingAs(User::factory()->create());
    }

    private function newBlocks(): array
    {
        return [
            'a' => ['type' => 'rich_text', 'data' => [
                'title' => 'Yeni blok', 'content' => '<p>Merhaba</p><script>alert(1)</script>',
                'is_active' => true, 'visibility' => 'mobile', 'variant' => 'narrow', 'anchor' => null,
            ]],
        ];
    }

    #[Test]
    public function saving_a_draft_does_not_change_the_published_blocks(): void
    {
        $published = $this->home->blocks;

        Livewire::test(ManageHomepage::class)
            ->set('data.draft_blocks', $this->newBlocks())
            ->call('saveDraft')
            ->assertHasNoErrors();

        $this->home->refresh();
        $this->assertSame($published, $this->home->blocks);
        $this->assertSame('Yeni blok', $this->home->draft_blocks[0]['data']['title']);
        $this->assertSame('mobile', $this->home->draft_blocks[0]['data']['visibility']);
        $this->assertSame('<p>Merhaba</p>', $this->home->draft_blocks[0]['data']['content'], 'Zengin metin temizlenmeli');
        $this->assertTrue($this->home->hasUnpublishedChanges());
    }

    #[Test]
    public function publishing_copies_the_draft_to_the_live_blocks(): void
    {
        Livewire::test(ManageHomepage::class)
            ->set('data.draft_blocks', $this->newBlocks())
            ->call('publish')
            ->assertHasNoErrors();

        $this->home->refresh();
        $this->assertSame('Yeni blok', $this->home->blocks[0]['data']['title']);
        $this->assertFalse($this->home->hasUnpublishedChanges());
        $this->assertNotNull($this->home->published_at);
    }

    #[Test]
    public function seeded_homepage_contains_the_expected_sections(): void
    {
        $types = array_column($this->home->blocks, 'type');

        foreach (['hero_slider', 'category_cards', 'featured_products', 'latest_products', 'featured_brands',
            'about_intro', 'features', 'stats', 'promo_banners', 'whatsapp_cta', 'contact_cta'] as $type) {
            $this->assertContains($type, $types);
        }
    }
}
