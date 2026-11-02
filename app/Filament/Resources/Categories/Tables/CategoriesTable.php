<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent.parent')->withCount(['primaryProducts', 'children']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image')->label('')->disk('public')->imageHeight(36),
                TextColumn::make('name')
                    ->label('Kategori')
                    ->searchable()
                    ->formatStateUsing(fn (Category $record) => $record->fullName())
                    ->description(fn (Category $record) => $record->summary ? str($record->summary)->limit(60) : null),
                TextColumn::make('primary_products_count')->label('Ürün')->numeric()->sortable(),
                TextColumn::make('children_count')->label('Alt kategori')->numeric(),
                ToggleColumn::make('is_active')->label('Aktif'),
                ToggleColumn::make('show_in_menu')->label('Menüde'),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Üst kategori')
                    ->options(fn () => Category::query()->roots()->orderBy('name')->pluck('name', 'id')),
                TernaryFilter::make('is_active')->label('Aktif'),
                TrashedFilter::make(),
            ])
            ->recordActions([
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
