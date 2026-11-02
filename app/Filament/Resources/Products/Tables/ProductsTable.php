<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['brand', 'category', 'coverImage']))
            ->defaultSort('updated_at', 'desc')
            ->searchPlaceholder('Ad, kod veya marka ara')
            ->columns([
                ImageColumn::make('coverImage.path')->label('')->disk('public')->imageHeight(44),
                TextColumn::make('name')
                    ->label('Ürün')
                    ->searchable(query: fn (Builder $query, string $search) => $query->search($search))
                    ->sortable()
                    ->limit(50)
                    ->description(fn (Product $record) => $record->code),
                TextColumn::make('brand.name')->label('Marka')->sortable()->placeholder('—'),
                TextColumn::make('category.name')->label('Ana kategori')->placeholder('—')->toggleable(),
                ToggleColumn::make('is_featured')->label('Öne çıkan'),
                ToggleColumn::make('is_active')->label('Aktif'),
                TextColumn::make('updated_at')->label('Güncelleme')->since()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('brand_id')
                    ->label('Marka')
                    ->options(fn () => Brand::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
                SelectFilter::make('category')
                    ->label('Kategori (alt kategoriler dahil)')
                    ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        if (blank($data['value'] ?? null)) {
                            return;
                        }

                        $ids = Category::descendantIdsOf((int) $data['value']);
                        $query->where(fn (Builder $q) => $q
                            ->whereIn('category_id', $ids)
                            ->orWhereHas('categories', fn (Builder $c) => $c->whereIn('categories.id', $ids)));
                    }),
                TernaryFilter::make('is_active')->label('Aktif'),
                TernaryFilter::make('is_featured')->label('Öne çıkan'),
                TernaryFilter::make('has_image')
                    ->label('Görsel')
                    ->trueLabel('Görseli olanlar')
                    ->falseLabel('Görseli olmayanlar')
                    ->queries(
                        true: fn (Builder $q) => $q->has('images'),
                        false: fn (Builder $q) => $q->doesntHave('images'),
                    ),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Kopyala')
                    ->excludeAttributes(['slug'])
                    ->mutateRecordDataUsing(fn (array $data) => [
                        ...$data,
                        'name' => $data['name'].' (kopya)',
                        'code' => $data['code'].'-KOPYA-'.Str::upper(Str::random(4)),
                        'is_active' => false,
                    ])
                    ->successRedirectUrl(fn (Product $replica) => route('filament.admin.resources.urunler.edit', $replica)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Aktif yap')
                        ->icon('heroicon-o-eye')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('Pasif yap')
                        ->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('feature')
                        ->label('Öne çıkar')
                        ->icon('heroicon-o-star')
                        ->action(fn (Collection $records) => $records->each->update(['is_featured' => true]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
