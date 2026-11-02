<?php

namespace Tests\Feature\Admin;

use App\Enums\ContentStatus;
use App\Filament\Resources\CorporatePages\Pages\CreateCorporatePage;
use App\Filament\Resources\CorporatePages\Pages\ListCorporatePages;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CorporatePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PageSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function the_ten_starting_pages_are_seeded_as_drafts_and_home_is_not_listed(): void
    {
        $corporate = Page::query()->corporate()->get();

        $this->assertCount(10, $corporate);
        $this->assertTrue($corporate->every(fn (Page $p) => $p->status === ContentStatus::Draft));

        Livewire::test(ListCorporatePages::class)
            ->assertCanSeeTableRecords($corporate)
            ->assertCanNotSeeTableRecords([Page::home()]);
    }

    #[Test]
    public function an_editor_can_create_a_page_and_unsafe_html_is_removed(): void
    {
        Livewire::test(CreateCorporatePage::class)
            ->fillForm([
                'title' => 'Üretim Tesisimiz',
                'status' => ContentStatus::Published->value,
                'type' => 'standard',
                'content' => '<h2>Tesis</h2><p onclick="x()">Metin</p><script>alert(1)</script>',
                'meta_title' => 'Tesis',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $page = Page::query()->where('title', 'Üretim Tesisimiz')->firstOrFail();

        $this->assertSame('uretim-tesisimiz', $page->slug);
        $this->assertStringNotContainsString('script', $page->content);
        $this->assertStringNotContainsString('onclick', $page->content);
        $this->assertStringContainsString('<h2>Tesis</h2>', $page->content);
        $this->assertTrue($page->isPublished());
        $this->assertSame(auth()->id(), $page->updated_by);
    }

    #[Test]
    public function slugs_stay_unique(): void
    {
        $page = Page::query()->create(['title' => 'Hakkımızda', 'type' => 'standard', 'status' => 'draft']);

        $this->assertSame('hakkimizda-2', $page->slug);
    }

    #[Test]
    public function scheduled_pages_are_not_published_yet(): void
    {
        $page = Page::query()->create([
            'title' => 'Yakında', 'type' => 'standard', 'status' => 'published', 'published_at' => now()->addDay(),
        ]);

        $this->assertFalse($page->isPublished());
        $this->assertFalse(Page::query()->published()->whereKey($page->id)->exists());
    }
}
