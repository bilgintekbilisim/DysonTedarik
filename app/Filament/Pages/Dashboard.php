<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Catalogs\CatalogResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Slides\SlideResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

/** Kontrol paneli: özet, talepler ve sık kullanılan işlemlere kısayollar. */
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Kontrol Paneli';

    public function getSubheading(): ?string
    {
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Günaydın' : ($hour < 18 ? 'İyi günler' : 'İyi akşamlar');

        return $greeting.', '.auth()->user()?->name.'. Sitenin genel durumu ve son talepler aşağıda.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('site')
                ->label('Siteyi görüntüle')
                ->icon(Heroicon::OutlinedGlobeAlt)
                ->color('gray')
                ->url(url('/'), shouldOpenInNewTab: true),
            ActionGroup::make([
                Action::make('product')->label('Yeni ürün')->icon(Heroicon::OutlinedCube)
                    ->url(fn () => ProductResource::getUrl('create'))->visible(fn () => ProductResource::canCreate()),
                Action::make('slide')->label('Yeni slayt')->icon(Heroicon::OutlinedPhoto)
                    ->url(fn () => SlideResource::getUrl('create'))->visible(fn () => SlideResource::canCreate()),
                Action::make('catalog')->label('Yeni e-katalog')->icon(Heroicon::OutlinedBookOpen)
                    ->url(fn () => CatalogResource::getUrl('create'))->visible(fn () => CatalogResource::canCreate()),
                Action::make('homepage')->label('Ana sayfayı düzenle')->icon(Heroicon::OutlinedHome)
                    ->url(fn () => ManageHomepage::getUrl())->visible(fn () => ManageHomepage::canAccess()),
                Action::make('settings')->label('Site ayarları')->icon(Heroicon::OutlinedCog6Tooth)
                    ->url(fn () => ManageSettings::getUrl())->visible(fn () => ManageSettings::canAccess()),
            ])
                ->label('Hızlı işlemler')
                ->icon(Heroicon::OutlinedBolt)
                ->button()
                ->color('primary'),
        ];
    }
}
