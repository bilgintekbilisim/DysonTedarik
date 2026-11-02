<?php

namespace App\Filament\Resources\Slides;

use App\Filament\Resources\Slides\Pages\CreateSlide;
use App\Filament\Resources\Slides\Pages\EditSlide;
use App\Filament\Resources\Slides\Pages\ListSlides;
use App\Filament\Resources\Slides\Schemas\SlideForm;
use App\Filament\Resources\Slides\Tables\SlidesTable;
use App\Models\Slide;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SlideResource extends Resource
{
    protected static ?string $model = Slide::class;

    protected static ?string $slug = 'slider-banner';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'slayt / banner';

    protected static ?string $pluralModelLabel = 'Slider / Banner';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return SlideForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SlidesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSlides::route('/'),
            'create' => CreateSlide::route('/create'),
            'edit' => EditSlide::route('/{record}/edit'),
        ];
    }
}
