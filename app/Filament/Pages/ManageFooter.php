<?php

namespace App\Filament\Pages;

use App\Filament\Support\SettingsPage;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageFooter extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static string|UnitEnum|null $navigationGroup = 'Görünüm';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Footer Yönetimi';

    protected static ?string $title = 'Footer Yönetimi';

    protected static ?string $slug = 'footer';

    protected function groups(): array
    {
        return ['footer'];
    }

    protected function fields(): array
    {
        return [
            Section::make('Footer içeriği')
                ->schema([
                    Textarea::make('footer.description')
                        ->label('Kısa firma açıklaması')
                        ->rows(3)
                        ->maxLength(400),
                    TextInput::make('footer.copyright')
                        ->label('Telif hakkı yazısı')
                        ->maxLength(200)
                        ->helperText('{yil} yazarsanız güncel yıl ile değiştirilir. Ör: © {yil} Dyson Tedarik. Tüm hakları saklıdır.'),
                ]),
            Section::make('Gösterilecek alanlar')
                ->columns(2)
                ->schema([
                    Toggle::make('footer.show_categories')->label('Ürün kategorileri sütunu')->default(true),
                    TextInput::make('footer.category_limit')->label('Kategori sayısı')->numeric()->minValue(1)->maxValue(20)->default(8),
                    Toggle::make('footer.show_contact')->label('İletişim bilgileri sütunu')->default(true),
                    Toggle::make('footer.show_social')->label('Sosyal medya ikonları')->default(true),
                ]),
            Text::make('Logo, adres, telefon, e-posta, WhatsApp ve sosyal medya bağlantıları "Site Ayarları"ndan; footer bağlantı sütunları "Menü Yönetimi"ndeki Footer menülerinden gelir.'),
        ];
    }
}
