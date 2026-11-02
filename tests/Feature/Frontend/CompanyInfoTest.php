<?php

namespace Tests\Feature\Frontend;

use App\Filament\Pages\ManageSettings;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\CompanyInfoSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyInfoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([SettingsSeeder::class, CompanyInfoSeeder::class, PageSeeder::class, MenuSeeder::class]);
    }

    #[Test]
    public function the_footer_shows_contact_and_legal_information(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Ferhatpaşa Mah. 77 Sok. No: 24/1, 34888 Ataşehir / İstanbul')
            ->assertSee('href="tel:+905304221728"', false)
            ->assertSee('Muhasebe:')
            ->assertSee('muhasebe@dysontedarik.com')
            ->assertSee('DYSON TEDARİK YAPI VE SAN. ÜRÜNLERİ TİC. A.Ş.')
            ->assertSee('0733130534700001')
            ->assertSee('Küçükyalı')
            ->assertSee('https://wa.me/905304221728', false)
            ->assertSee('"taxID":"7331305347"', false)
            ->assertSee('"postalCode":"34888"', false);
    }

    #[Test]
    public function the_contact_page_lists_both_lines_and_company_details(): void
    {
        $this->get('/iletisim')
            ->assertOk()
            ->assertSee('Telefon — Yetkili')
            ->assertSee('Telefon — Muhasebe')
            ->assertSee('E-posta — Muhasebe')
            ->assertSee('Firma bilgileri')
            ->assertSee('110395');
    }

    #[Test]
    public function legal_details_can_be_hidden_from_the_footer_and_seeder_does_not_overwrite_edits(): void
    {
        app(SettingsService::class)->setMany(['company' => ['show_in_footer' => false], 'contact' => ['phone' => '+90 212 111 11 11']]);
        $this->seed(CompanyInfoSeeder::class);

        $this->get('/')->assertDontSee('0733130534700001')->assertSee('+90 212 111 11 11');
        $this->get('/iletisim')->assertSee('0733130534700001');
    }

    #[Test]
    public function the_settings_page_validates_mersis(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageSettings::class)
            ->assertSet('data.company.tax_office', 'Küçükyalı')
            ->set('data.company.mersis', '733130534700001')
            ->call('save')
            ->assertHasErrors(['data.company.mersis']);
    }
}
