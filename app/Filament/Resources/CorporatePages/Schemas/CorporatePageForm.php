<?php

namespace App\Filament\Resources\CorporatePages\Schemas;

use App\Enums\ContentStatus;
use App\Enums\PageType;
use App\Filament\Support\Fields;
use App\Filament\Support\PageBlocks;
use App\Models\Page;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class CorporatePageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Sayfa içeriği')
                    ->columnSpan(2)
                    ->schema([
                        Fields::titleWithSlug(),
                        Fields::slug(),
                        Textarea::make('excerpt')
                            ->label('Kısa açıklama')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Sayfa başlığının altında ve arama sonuçlarında kullanılır.'),
                        Fields::image('cover_image', 'pages')
                            ->label('Kapak görseli'),
                        Fields::richText('content', 'pages')
                            ->label('İçerik'),
                    ]),
                Section::make('Yayın')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('status')
                            ->label('Durum')
                            ->options(ContentStatus::class)
                            ->default(ContentStatus::Draft)
                            ->selectablePlaceholder(false)
                            ->required(),
                        DateTimePicker::make('published_at')
                            ->label('Yayınlanma tarihi')
                            ->native(false)
                            ->seconds(false)
                            ->helperText('İleri bir tarih seçilirse sayfa o tarihte görünür.'),
                        Select::make('type')
                            ->label('Sayfa türü')
                            ->options(PageType::corporateOptions())
                            ->default(PageType::Standard->value)
                            ->selectablePlaceholder(false)
                            ->required(),
                        Toggle::make('show_in_menu')
                            ->label('Kurumsal menüde göster'),
                        TextInput::make('sort_order')
                            ->label('Sıra')
                            ->numeric()
                            ->default(0),
                        Text::make(fn (?Page $record) => $record
                            ? 'Son güncelleme: '.$record->updated_at?->format('d.m.Y H:i').($record->editor ? ' — '.$record->editor->name : '')
                            : 'Henüz kaydedilmedi.'),
                    ]),
                Section::make('İçerik blokları')
                    ->description('Metnin altına eklenecek, sürükle-bırak ile sıralanabilir bloklar: metin+görsel, ikonlu özellik listesi, istatistik, iletişim çağrısı.')
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        PageBlocks::builder('blocks', home: false),
                    ]),
                Fields::seoSection('pages/seo')
                    ->columnSpanFull(),
            ]);
    }
}
