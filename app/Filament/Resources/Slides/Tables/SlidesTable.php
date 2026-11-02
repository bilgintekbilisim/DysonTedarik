<?php

namespace App\Filament\Resources\Slides\Tables;

use App\Enums\SlideGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SlidesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image_desktop')
                    ->label('Görsel')
                    ->disk('public')
                    ->imageHeight(48),
                TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('group')
                    ->label('Yer')
                    ->badge(),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
                TextColumn::make('starts_at')
                    ->label('Başlangıç')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Bitiş')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label('Gösterim yeri')
                    ->options(SlideGroup::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
