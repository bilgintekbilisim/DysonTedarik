<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Services\SettingsService;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->brandName('Dyson Tedarik')
            ->brandLogo(fn () => $this->settingUrl('general.logo'))
            ->brandLogoHeight('2.75rem')
            ->favicon(fn () => $this->settingUrl('general.favicon'))
            ->colors([
                'primary' => Color::Orange,
                'gray' => Color::Slate,
                'info' => Color::Blue,
            ])
            ->maxContentWidth(Width::Full)
            ->globalSearchKeyBindings(['ctrl+k', 'command+k'])
            ->unsavedChangesAlerts()
            ->userMenuItems([
                Action::make('site')
                    ->label('Siteyi görüntüle')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->url(fn () => url('/'), shouldOpenInNewTab: true),
            ])
            ->navigationGroups([
                NavigationGroup::make('Talepler'),
                NavigationGroup::make('Katalog'),
                NavigationGroup::make('İçerik Yönetimi'),
                NavigationGroup::make('Görünüm'),
                NavigationGroup::make('SEO'),
                NavigationGroup::make('Sistem'),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->databaseTransactions()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /** Site Ayarları'ndaki logo / favicon; ayar yoksa Filament kendi varsayılanını kullanır. */
    private function settingUrl(string $key): ?string
    {
        $path = app(SettingsService::class)->get($key);

        return $path ? Storage::disk('public')->url($path) : null;
    }
}
