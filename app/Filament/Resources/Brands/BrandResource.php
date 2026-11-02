<?php

namespace App\Filament\Resources\Brands;

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Filament\Support\Fields;
use App\Models\Brand;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $slug = 'markalar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'marka';

    protected static ?string $pluralModelLabel = 'Markalar';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Marka')
                ->columnSpan(2)
                ->schema([
                    Fields::titleWithSlug('name', 'Marka adı'),
                    Fields::slug(),
                    TextInput::make('website')->label('Web sitesi')->url()->maxLength(255),
                    Fields::richText('description', 'brands')->label('Açıklama'),
                ]),
            Section::make('Görünüm')
                ->columnSpan(1)
                ->schema([
                    Toggle::make('is_active')->label('Aktif')->default(true),
                    Toggle::make('is_featured')->label('Ana sayfada göster')->helperText('"Öne çıkan markalar" bloğunda listelenir.'),
                    TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                ]),
            Section::make('Görseller')
                ->columnSpanFull()
                ->schema([
                    Grid::make(2)->schema([
                        Fields::image('logo', 'brands')
                            ->label('Logo')
                            ->imageEditor(false)
                            ->helperText('Şeffaf zeminli PNG veya WEBP önerilir, en fazla 5 MB.'),
                        Fields::image('cover_image', 'brands')
                            ->label('Kapak görseli')
                            ->helperText('Marka sayfasının üst alanı. Önerilen 1600×400 px.'),
                    ]),
                ]),
            Fields::seoSection('brands/seo')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('products'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('logo')->label('Logo')->disk('public')->imageHeight(36),
                TextColumn::make('name')->label('Marka')->searchable()->sortable(),
                TextColumn::make('products_count')->label('Ürün')->numeric()->sortable(),
                ToggleColumn::make('is_featured')->label('Ana sayfada'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->filters([
                TernaryFilter::make('is_featured')->label('Ana sayfada'),
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

    public static function getPages(): array
    {
        return [
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'edit' => EditBrand::route('/{record}/edit'),
        ];
    }
}
