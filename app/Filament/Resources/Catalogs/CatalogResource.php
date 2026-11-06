<?php

namespace App\Filament\Resources\Catalogs;

use App\Filament\Resources\Catalogs\Pages\CreateCatalog;
use App\Filament\Resources\Catalogs\Pages\EditCatalog;
use App\Filament\Resources\Catalogs\Pages\ListCatalogs;
use App\Filament\Support\Fields;
use App\Models\Catalog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
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

class CatalogResource extends Resource
{
    protected static ?string $model = Catalog::class;

    protected static ?string $slug = 'e-kataloglar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'e-katalog';

    protected static ?string $pluralModelLabel = 'E-Kataloglar';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Katalog')
                ->columnSpan(2)
                ->schema([
                    Fields::titleWithSlug('title', 'Katalog adı'),
                    Fields::slug(),
                    Textarea::make('description')->label('Kısa açıklama')->rows(3)->maxLength(500),
                    Fields::pdf('file', 'catalogs')->label('PDF dosyası')->required(),
                ]),
            Section::make('Görünüm')
                ->columnSpan(1)
                ->schema([
                    Toggle::make('is_active')->label('Yayında')->default(true),
                    Select::make('brand_id')->label('Marka')->relationship('brand', 'name')->searchable()->preload(),
                    TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                    Fields::image('cover_image', 'catalogs')
                        ->label('Kapak görseli')
                        ->helperText('Dikey (A4 oranında) görsel önerilir, en fazla 5 MB.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('cover_image')->label('Kapak')->disk('public')->imageHeight(56),
                TextColumn::make('title')->label('Katalog')->searchable()->sortable()
                    ->description(fn (Catalog $record) => $record->readableSize()),
                TextColumn::make('brand.name')->label('Marka')->placeholder('—')->sortable(),
                TextColumn::make('updated_at')->label('Güncellendi')->since()->sortable(),
                ToggleColumn::make('is_active')->label('Yayında'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Yayında'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('PDF')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Catalog $record) => $record->fileUrl(), shouldOpenInNewTab: true),
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
            'index' => ListCatalogs::route('/'),
            'create' => CreateCatalog::route('/create'),
            'edit' => EditCatalog::route('/{record}/edit'),
        ];
    }
}
