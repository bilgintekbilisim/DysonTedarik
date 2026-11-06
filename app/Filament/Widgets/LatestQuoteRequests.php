<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\QuoteRequests\QuoteRequestResource;
use App\Models\QuoteRequest;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestQuoteRequests extends TableWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Son teklif talepleri';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', QuoteRequest::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(QuoteRequest::query()->latest()->limit(6))
            ->paginated(false)
            ->columns([
                TextColumn::make('created_at')->label('Tarih')->since(),
                TextColumn::make('full_name')->label('Talep eden')->description(fn (QuoteRequest $r) => $r->company_name),
                TextColumn::make('product_name')->label('Ürün')->limit(30)->placeholder('—'),
                TextColumn::make('status')->label('Durum')->badge(),
            ])
            ->emptyStateHeading('Henüz teklif talebi yok')
            ->headerActions([
                Action::make('all')->label('Tümü')->link()->url(QuoteRequestResource::getUrl()),
            ]);
    }
}
