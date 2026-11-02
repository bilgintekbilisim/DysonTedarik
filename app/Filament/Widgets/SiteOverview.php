<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\InquiryStatus;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\Product;
use App\Models\QuoteRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Yeni teklif talepleri', QuoteRequest::query()->where('status', InquiryStatus::New)->count())
                ->description('Toplam: '.QuoteRequest::query()->count())
                ->icon('heroicon-o-document-currency-dollar')
                ->color('warning'),
            Stat::make('Yeni iletişim mesajları', ContactMessage::query()->where('status', InquiryStatus::New)->count())
                ->icon('heroicon-o-envelope')
                ->color('info'),
            Stat::make('Aktif ürünler', Product::query()->where('is_active', true)->count())
                ->description('Toplam: '.Product::query()->count())
                ->icon('heroicon-o-cube'),
            Stat::make('Yayındaki sayfalar', Page::query()->corporate()->where('status', ContentStatus::Published)->count())
                ->icon('heroicon-o-document-text'),
        ];
    }
}
