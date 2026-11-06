<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestContactMessages extends TableWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Son iletişim mesajları';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', ContactMessage::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ContactMessage::query()->latest()->limit(6))
            ->paginated(false)
            ->columns([
                TextColumn::make('created_at')->label('Tarih')->since(),
                TextColumn::make('full_name')->label('Gönderen'),
                TextColumn::make('subject')->label('Konu')->limit(30)->placeholder('—'),
                TextColumn::make('status')->label('Durum')->badge(),
            ])
            ->emptyStateHeading('Henüz mesaj yok')
            ->headerActions([
                Action::make('all')->label('Tümü')->link()->url(ContactMessageResource::getUrl()),
            ]);
    }
}
