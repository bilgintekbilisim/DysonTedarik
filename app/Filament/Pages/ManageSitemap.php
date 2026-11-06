<?php

namespace App\Filament\Pages;

use App\Services\SitemapGenerator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Sitemap: kapsam özeti, yeniden oluşturma ve arama motorlarına bildirme adresleri. */
class ManageSitemap extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Sitemap';

    protected static ?string $title = 'Sitemap';

    protected static ?string $slug = 'sitemap';

    /** @var array<string, int> */
    public array $counts = [];

    public ?int $generatedAt = null;

    public function mount(): void
    {
        $this->refreshSummary();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open')
                ->label('sitemap.xml\'i aç')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(route('sitemap'), shouldOpenInNewTab: true),
            Action::make('regenerate')
                ->label('Sitemap\'i yeniden oluştur')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function (SitemapGenerator $sitemap) {
                    $sitemap->regenerate();
                    $this->refreshSummary();
                    Notification::make()->success()->title('Sitemap yeniden oluşturuldu: '.array_sum($this->counts).' adres.')->send();
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $entries = collect($this->counts)->map(fn (int $count, string $type) => TextEntry::make('count_'.md5($type))->label($type)->state($count)->badge())->values()->all();

        return $schema->components([
            Section::make('Sitemap kapsamı')
                ->description('Yayında olan, noindex işaretlenmemiş ve başka adrese canonical verilmemiş kayıtlar eklenir. İçerik kaydedildiğinde sitemap kendiliğinden yenilenir.')
                ->schema([
                    Grid::make(['default' => 2, 'md' => 3, 'xl' => 6])->schema([
                        TextEntry::make('total')->label('Toplam adres')->state(array_sum($this->counts))->weight('bold'),
                        ...$entries,
                    ]),
                    TextEntry::make('generated')
                        ->label('Son oluşturma')
                        ->state($this->generatedAt ? now()->setTimestamp($this->generatedAt)->format('d.m.Y H:i') : 'Henüz oluşturulmadı'),
                ]),
            Section::make('Arama motorlarına bildirme')
                ->schema([
                    TextEntry::make('url')->label('Sitemap adresi')->state(route('sitemap'))->copyable(),
                    TextEntry::make('robots')->label('robots.txt')->state(route('robots'))->url(route('robots'), shouldOpenInNewTab: true),
                    Text::make('Google Search Console → Dizin oluşturma → Site haritaları bölümüne yukarıdaki adresi ekleyin. Bing için Bing Webmaster Tools aynı adresi kabul eder. robots.txt sitemap adresini zaten bildirir.'),
                ]),
        ]);
    }

    private function refreshSummary(): void
    {
        $sitemap = app(SitemapGenerator::class);
        $sitemap->xml();
        $this->counts = collect($sitemap->urls())->countBy('type')->all();
        $this->generatedAt = $sitemap->generatedAt();
    }
}
