<?php

namespace Tests\Feature\Admin;

use App\Models\Menu;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PageSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([SettingsSeeder::class, PageSeeder::class, MenuSeeder::class]);
    }

    /** Editörün de erişebildiği ekranlar. */
    public static function contentScreens(): array
    {
        return [
            'dashboard' => ['/admin'],
            'ana sayfa' => ['/admin/ana-sayfa'],
            'kurumsal sayfalar' => ['/admin/kurumsal-sayfalar'],
            'yeni kurumsal sayfa' => ['/admin/kurumsal-sayfalar/create'],
            'slider' => ['/admin/slider-banner'],
            'yeni slider' => ['/admin/slider-banner/create'],
            'medya' => ['/admin/medya'],
            'menüler' => ['/admin/menuler'],
            'footer' => ['/admin/footer'],
            'teklifler' => ['/admin/teklif-talepleri'],
            'iletişim' => ['/admin/iletisim-mesajlari'],
            'yönlendirmeler' => ['/admin/yonlendirmeler'],
        ];
    }

    public static function adminOnlyScreens(): array
    {
        return [
            'site ayarları' => ['/admin/site-ayarlari'],
            'kullanıcılar' => ['/admin/kullanicilar'],
        ];
    }

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
    }

    #[Test]
    public function inactive_users_cannot_enter_the_panel(): void
    {
        $this->actingAs(User::factory()->admin()->inactive()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    #[Test]
    #[DataProvider('contentScreens')]
    public function editors_can_open_content_screens(string $url): void
    {
        $this->actingAs(User::factory()->create())->get($url)->assertOk();
    }

    #[Test]
    #[DataProvider('adminOnlyScreens')]
    public function editors_cannot_open_admin_only_screens(string $url): void
    {
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    }

    #[Test]
    #[DataProvider('adminOnlyScreens')]
    #[DataProvider('contentScreens')]
    public function admins_can_open_every_screen(string $url): void
    {
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
    }

    #[Test]
    public function seeded_records_have_working_edit_screens(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::query()->where('slug', 'hakkimizda')->firstOrFail();
        $menu = Menu::query()->firstOrFail();

        $this->actingAs($admin)->get("/admin/kurumsal-sayfalar/{$page->id}/edit")->assertOk()->assertSee('Hakkımızda');
        $this->actingAs($admin)->get("/admin/menuler/{$menu->id}/edit")->assertOk();
    }
}
