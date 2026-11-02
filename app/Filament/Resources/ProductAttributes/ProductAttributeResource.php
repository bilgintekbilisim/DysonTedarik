<?php

namespace App\Filament\Resources\ProductAttributes;

use App\Filament\Resources\ProductAttributes\Pages\CreateProductAttribute;
use App\Filament\Resources\ProductAttributes\Pages\EditProductAttribute;
use App\Filament\Resources\ProductAttributes\Pages\ListProductAttributes;
use App\Filament\Support\Fields;
use App\Models\ProductAttribute;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductAttributeResource extends Resource
{
    protected static ?string $model = ProductAttribute::class;

    protected static ?string $slug = 'urun-ozellikleri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Ürün Özellikleri';

    protected static ?string $modelLabel = 'ürün özelliği';

    protected static ?string $pluralModelLabel = 'Ürün Özellikleri';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Özellik')
                ->description('Kategori sayfalarında filtre olarak kullanılacak özellikler (ör. Renk, Hacim, Kür süresi).')
                ->columns(2)
                ->schema([
                    Fields::titleWithSlug('name', 'Özellik adı'),
                    Fields::slug()->helperText('Filtre adresinde kullanılır: ?renk=beyaz'),
                    TextInput::make('unit')->label('Birim')->maxLength(30)->placeholder('ml, kg, mm'),
                    TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                    Toggle::make('is_filterable')->label('Kategori sayfasında filtre olarak göster')->default(true),
                ]),
            Section::make('Değerler')
                ->schema([
                    Repeater::make('values')
                        ->hiddenLabel()
                        ->relationship()
                        ->orderColumn('sort_order')
                        ->reorderableWithDragAndDrop()
                        ->simple(
                            TextInput::make('value')->label('Değer')->required()->maxLength(100)->distinct(),
                        )
                        ->addActionLabel('Değer ekle')
                        ->helperText('Bir değeri silmek, onu kullanan ürünlerden de kaldırır.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('values')->withCount('values'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Özellik')->searchable()->description(fn (ProductAttribute $r) => $r->unit),
                TextColumn::make('values.value')->label('Değerler')->badge()->limitList(6)->expandableLimitedList(),
                TextColumn::make('values_count')->label('Değer sayısı')->numeric(),
                IconColumn::make('is_filterable')->label('Filtre')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductAttributes::route('/'),
            'create' => CreateProductAttribute::route('/create'),
            'edit' => EditProductAttribute::route('/{record}/edit'),
        ];
    }
}
