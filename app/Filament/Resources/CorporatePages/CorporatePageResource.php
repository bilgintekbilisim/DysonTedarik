<?php

namespace App\Filament\Resources\CorporatePages;

use App\Filament\Resources\CorporatePages\Pages\CreateCorporatePage;
use App\Filament\Resources\CorporatePages\Pages\EditCorporatePage;
use App\Filament\Resources\CorporatePages\Pages\ListCorporatePages;
use App\Filament\Resources\CorporatePages\Schemas\CorporatePageForm;
use App\Filament\Resources\CorporatePages\Tables\CorporatePagesTable;
use App\Models\Page;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CorporatePageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $slug = 'kurumsal-sayfalar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'kurumsal sayfa';

    protected static ?string $pluralModelLabel = 'Kurumsal Sayfalar';

    protected static ?string $recordTitleAttribute = 'title';

    /** Ana sayfa kaydı ayrı "Ana Sayfa Yönetimi" ekranında düzenlenir. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->corporate()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return CorporatePageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CorporatePagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCorporatePages::route('/'),
            'create' => CreateCorporatePage::route('/create'),
            'edit' => EditCorporatePage::route('/{record}/edit'),
        ];
    }
}
