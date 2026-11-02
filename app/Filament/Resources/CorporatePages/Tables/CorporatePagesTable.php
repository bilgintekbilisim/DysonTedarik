<?php

namespace App\Filament\Resources\CorporatePages\Tables;

use App\Enums\ContentStatus;
use App\Enums\PageType;
use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;

class CorporatePagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->description(fn (Page $record) => '/'.$record->slug),
                TextColumn::make('type')
                    ->label('Tür')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge(),
                IconColumn::make('show_in_menu')
                    ->label('Menüde')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('Yayın tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Son güncelleme')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Durum')->options(ContentStatus::class),
                SelectFilter::make('type')->label('Tür')->options(PageType::corporateOptions()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Görüntüle')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Page $record) => Route::has('pages.show') ? route('pages.show', $record) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (Page $record) => $record->isPublished() && Route::has('pages.show')),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
