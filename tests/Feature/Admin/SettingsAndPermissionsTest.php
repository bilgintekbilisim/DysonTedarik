<?php

namespace Tests\Feature\Admin;

use App\Enums\MenuLocation;
use App\Filament\Pages\ManageFooter;
use App\Filament\Pages\ManageSettings;
use App\Models\Menu;
use App\Models\Page;
use App\Models\QuoteRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    #[Test]
    public function an_admin_can_update_site_settings_and_the_cache_is_refreshed(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $settings = app(SettingsService::class);
        $this->assertNull($settings->get('contact.phone'));

        Livewire::test(ManageSettings::class)
            ->set('data.general.site_name', 'Dyson Tedarik')
            ->set('data.contact.phone', '+90 212 000 00 00')
            ->set('data.contact.whatsapp', '+90 500 000 00 00')
            ->set('data.integrations.ga4_id', 'G-ABC123XYZ')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('+90 212 000 00 00', app(SettingsService::class)->get('contact.phone'));
        $this->assertSame(['phone' => '+90 212 000 00 00'], array_intersect_key(app(SettingsService::class)->group('contact'), ['phone' => 1]));
    }

    #[Test]
    public function analytics_fields_only_accept_ids_not_scripts(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageSettings::class)
            ->set('data.integrations.ga4_id', '<script>alert(1)</script>')
            ->call('save')
            ->assertHasErrors(['data.integrations.ga4_id']);

        $this->assertNull(Setting::query()->where('key', 'integrations.ga4_id')->first());
    }

    #[Test]
    public function an_editor_cannot_use_the_settings_page_but_can_edit_the_footer(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageSettings::class)->assertForbidden();

        Livewire::test(ManageFooter::class)
            ->set('data.footer.description', 'Yapı malzemeleri tedarikçisi.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Yapı malzemeleri tedarikçisi.', app(SettingsService::class)->get('footer.description'));
    }

    #[Test]
    public function the_footer_page_cannot_write_other_setting_groups(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageFooter::class)
            ->set('data.integrations', ['ga4_id' => 'G-HACKED1'])
            ->call('save');

        $this->assertNull(app(SettingsService::class)->get('integrations.ga4_id'));
    }

    #[Test]
    public function editors_cannot_delete_inquiries_or_users(): void
    {
        $editor = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $quote = QuoteRequest::query()->create(['full_name' => 'Ali', 'phone' => '1', 'email' => 'a@b.test']);

        $this->assertTrue($editor->can('update', $quote));
        $this->assertFalse($editor->can('delete', $quote));
        $this->assertFalse($editor->can('create', QuoteRequest::class));
        $this->assertTrue($admin->can('delete', $quote));
        $this->assertFalse($editor->can('viewAny', User::class));
        $this->assertFalse($admin->can('delete', $admin), 'Yönetici kendi hesabını silememeli');
    }

    #[Test]
    public function menu_links_to_unpublished_pages_are_hidden(): void
    {
        $this->seed([PageSeeder::class, MenuSeeder::class]);
        $item = Menu::query()->where('location', MenuLocation::FooterLegal)->firstOrFail()->items()->firstOrFail();

        $this->assertNull($item->resolveUrl(), 'Taslak sayfaya giden bağlantı gösterilmemeli');

        Page::query()->whereKey($item->page_id)->update(['status' => 'published']);
        $item->refresh()->load('page');

        // Ön yüz rotası (pages.show) Aşama 4'te eklenecek; o zamana kadar rota yoksa null döner.
        $this->assertSame(Route::has('pages.show') ? route('pages.show', $item->page) : null, $item->resolveUrl());
    }
}
