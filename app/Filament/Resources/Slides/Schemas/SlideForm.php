<?php

namespace App\Filament\Resources\Slides\Schemas;

use App\Enums\SlideGroup;
use App\Filament\Support\Fields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SlideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('İçerik')
                    ->columnSpan(2)
                    ->schema([
                        Select::make('group')
                            ->label('Gösterim yeri')
                            ->options(SlideGroup::class)
                            ->default(SlideGroup::Hero)
                            ->selectablePlaceholder(false)
                            ->required(),
                        TextInput::make('title')
                            ->label('Başlık')
                            ->required()
                            ->maxLength(150),
                        Textarea::make('description')
                            ->label('Açıklama')
                            ->rows(3)
                            ->maxLength(400),
                        Grid::make(2)->schema([
                            TextInput::make('button_text')
                                ->label('Buton metni')
                                ->maxLength(50),
                            Fields::link('button_url', 'Buton bağlantısı'),
                        ]),
                    ]),
                Section::make('Yayın')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        DateTimePicker::make('starts_at')
                            ->label('Yayın başlangıcı')
                            ->native(false)
                            ->seconds(false),
                        DateTimePicker::make('ends_at')
                            ->label('Yayın bitişi')
                            ->native(false)
                            ->seconds(false)
                            ->afterOrEqual('starts_at'),
                        TextInput::make('sort_order')
                            ->label('Sıra')
                            ->numeric()
                            ->default(0),
                    ]),
                Section::make('Görseller')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Fields::image('image_desktop', 'slides')
                            ->label('Masaüstü görseli')
                            ->helperText('Önerilen 1920×700 px. JPG, PNG veya WEBP — en fazla 5 MB.')
                            ->required(),
                        Fields::image('image_mobile', 'slides')
                            ->label('Mobil görsel')
                            ->helperText('Önerilen 800×1000 px. Boş bırakılırsa masaüstü görseli kullanılır.'),
                        TextInput::make('image_alt')
                            ->label('Görsel alternatif metni (ALT)')
                            ->maxLength(150)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
