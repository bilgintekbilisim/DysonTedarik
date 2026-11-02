<?php

namespace App\Filament\Support;

use App\Enums\InquiryStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Teklif talepleri ve iletişim mesajları için ortak liste, filtre ve durum formu.
 * Kayıtlar yalnızca site formlarından gelir; panelde yalnızca durum ve yönetici notu değişir.
 */
class InboxTable
{
    public static function statusForm(): array
    {
        return [
            Select::make('status')
                ->label('Durum')
                ->options(InquiryStatus::class)
                ->selectablePlaceholder(false)
                ->required(),
            Textarea::make('admin_note')
                ->label('Yönetici notu')
                ->rows(4)
                ->maxLength(2000)
                ->helperText('Yalnızca panelde görünür.'),
        ];
    }

    public static function configure(Table $table, array $columns): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns($columns)
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(InquiryStatus::class),
                Filter::make('created_at')
                    ->label('Tarih aralığı')
                    ->schema([
                        DatePicker::make('from')->label('Başlangıç'),
                        DatePicker::make('until')->label('Bitiş'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->label('Durum / not'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
