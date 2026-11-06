<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\InquiryStatus;
use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Resources\Catalogs\CatalogResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\CorporatePages\CorporatePageResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\QuoteRequests\QuoteRequestResource;
use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\Product;
use App\Models\QuoteRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $weekly = collect(range(6, 0))->map(fn (int $days) => QuoteRequest::query()->whereDate('created_at', now()->subDays($days))->count())->all();

        return [
            Stat::make('Yeni teklif talepleri', QuoteRequest::query()->where('status', InquiryStatus::New)->count())
                ->description('Son 30 gün: '.QuoteRequest::query()->where('created_at', '>=', now()->subDays(30))->count().' · Toplam: '.QuoteRequest::query()->count())
                ->icon('heroicon-o-document-currency-dollar')
                ->chart($weekly)
                ->color('warning')
                ->url(QuoteRequestResource::getUrl()),
            Stat::make('Yeni iletişim mesajları', ContactMessage::query()->where('status', InquiryStatus::New)->count())
                ->description('Toplam: '.ContactMessage::query()->count())
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->url(ContactMessageResource::getUrl()),
            Stat::make('Aktif ürünler', Product::query()->where('is_active', true)->count())
                ->description('Toplam: '.Product::query()->count().' · Görselsiz: '.Product::query()->doesntHave('images')->count())
                ->icon('heroicon-o-cube')
                ->color('primary')
                ->url(ProductResource::getUrl()),
            Stat::make('Kategoriler', Category::query()->where('is_active', true)->count())
                ->description('Ana kategori: '.Category::query()->whereNull('parent_id')->count())
                ->icon('heroicon-o-squares-2x2')
                ->url(CategoryResource::getUrl()),
            Stat::make('Markalar', Brand::query()->where('is_active', true)->count())
                ->icon('heroicon-o-tag')
                ->url(BrandResource::getUrl()),
            Stat::make('E-Kataloglar', Catalog::query()->where('is_active', true)->count())
                ->description('Yayında olan PDF kataloglar')
                ->icon('heroicon-o-book-open')
                ->url(CatalogResource::getUrl()),
            Stat::make('Yayındaki sayfalar', Page::query()->corporate()->where('status', ContentStatus::Published)->count())
                ->description('Taslak: '.Page::query()->corporate()->where('status', '!=', ContentStatus::Published)->count())
                ->icon('heroicon-o-document-text')
                ->url(CorporatePageResource::getUrl()),
            Stat::make('Bu ay gelen talepler', QuoteRequest::query()->where('created_at', '>=', now()->startOfMonth())->count()
                + ContactMessage::query()->where('created_at', '>=', now()->startOfMonth())->count())
                ->description('Teklif + iletişim, '.now()->translatedFormat('F Y'))
                ->icon('heroicon-o-inbox-arrow-down'),
        ];
    }
}
