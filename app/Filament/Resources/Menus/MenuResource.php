<?php

namespace App\Filament\Resources\Menus;

use App\Enums\MenuLocation;
use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Resources\Menus\Pages\ListMenus;
use App\Filament\Resources\Menus\RelationManagers\ItemsRelationManager;
use App\Models\Menu;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $slug = 'menuler';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|UnitEnum|null $navigationGroup = 'Görünüm';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Menü Yönetimi';

    protected static ?string $modelLabel = 'menü';

    protected static ?string $pluralModelLabel = 'Menüler';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')
                    ->label('Menü adı')
                    ->required()
                    ->maxLength(100),
                Select::make('location')
                    ->label('Konum')
                    ->options(MenuLocation::class)
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Her konumda tek menü bulunur.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Menü')->searchable(),
                TextColumn::make('location')->label('Konum')->badge(),
                TextColumn::make('items_count')->label('Öğe sayısı')->counts('items'),
                TextColumn::make('updated_at')->label('Son güncelleme')->since(),
            ])
            ->recordActions([
                EditAction::make()->label('Öğeleri düzenle'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}
