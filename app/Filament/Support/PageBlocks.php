<?php

namespace App\Filament\Support;

use App\Models\Category;
use App\Support\Blocks\BlockRegistry;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

/**
 * Filament Builder için blok tanımları. Her blok türünün alanları burada,
 * görünümü ise resources/views/blocks altında sabittir; yönetici serbest HTML/JS giremez.
 */
class PageBlocks
{
    public static function builder(string $name = 'blocks', bool $home = true): Builder
    {
        $blocks = collect(BlockRegistry::types(includeHomeOnly: $home))
            ->map(fn (string $type) => self::block($type))
            ->all();

        return Builder::make($name)
            ->hiddenLabel()
            ->blocks($blocks)
            ->blockPickerColumns(2)
            ->blockPickerWidth('2xl')
            ->addActionLabel('Blok ekle')
            ->reorderableWithDragAndDrop()
            ->reorderableWithButtons()
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->blockNumbers(false)
            ->default([]);
    }

    private static function block(string $type): Block
    {
        $meta = BlockRegistry::BLOCKS[$type];

        return Block::make($type)
            ->label(function (?array $state) use ($meta) {
                $title = $state['title'] ?? null;
                $status = ($state['is_active'] ?? true) ? '' : ' (pasif)';

                return $meta['label'].($title ? " — {$title}" : '').$status;
            })
            ->icon($meta['icon'])
            ->schema([
                ...self::fields($type),
                self::displaySettings($meta['variants']),
            ]);
    }

    /** Her blokta ortak: aktiflik, cihaz görünürlüğü, tasarım varyantı, sayfa içi çapa. */
    private static function displaySettings(array $variants): Section
    {
        return Section::make('Görünüm ayarları')
            ->compact()
            ->collapsible()
            ->collapsed()
            ->columns(4)
            ->schema([
                Toggle::make('is_active')->label('Aktif')->default(true)->inline(false),
                Select::make('visibility')
                    ->label('Cihaz')
                    ->options(['all' => 'Tüm cihazlar', 'desktop' => 'Yalnızca masaüstü', 'mobile' => 'Yalnızca mobil'])
                    ->default('all')
                    ->selectablePlaceholder(false),
                Select::make('variant')
                    ->label('Tasarım varyantı')
                    ->options($variants)
                    ->default(array_key_first($variants))
                    ->selectablePlaceholder(false),
                TextInput::make('anchor')
                    ->label('Çapa (#)')
                    ->alphaDash()
                    ->maxLength(50),
            ]);
    }

    private static function heading(bool $subtitle = true): array
    {
        return array_filter([
            TextInput::make('title')->label('Başlık')->maxLength(150),
            $subtitle ? TextInput::make('subtitle')->label('Alt başlık')->maxLength(255) : null,
        ]);
    }

    private static function button(string $prefix = 'button'): Grid
    {
        return Grid::make(2)->schema([
            TextInput::make("{$prefix}_text")->label('Buton metni')->maxLength(50),
            Fields::link("{$prefix}_url", 'Buton bağlantısı'),
        ]);
    }

    private static function limit(int $default = 8): TextInput
    {
        return TextInput::make('limit')
            ->label('Gösterilecek adet')
            ->numeric()
            ->minValue(1)
            ->maxValue(24)
            ->default($default);
    }

    private static function fields(string $type): array
    {
        return match ($type) {
            'hero_slider' => [
                TextInput::make('title')->label('Yönetici notu')->helperText('Yalnızca panelde görünür. Slaytlar "Slider / Banner" modülünden yönetilir.'),
                Toggle::make('autoplay')->label('Otomatik geçiş')->default(true),
            ],
            'category_cards' => [
                ...self::heading(),
                Select::make('category_ids')
                    ->label('Kategoriler')
                    ->multiple()
                    ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id'))
                    ->helperText('Boş bırakılırsa aktif ana kategoriler sırasıyla gösterilir.'),
                self::limit(8),
            ],
            'featured_products', 'latest_products' => [
                ...self::heading(),
                self::limit(8),
                self::button(),
            ],
            'featured_brands' => [
                ...self::heading(),
                self::limit(12),
            ],
            'about_intro', 'text_image' => [
                TextInput::make('eyebrow')->label('Üst etiket')->maxLength(60),
                TextInput::make('title')->label('Başlık')->maxLength(150),
                Fields::richText('content', 'blocks')->label('Metin'),
                Grid::make(2)->schema([
                    Fields::image('image', 'blocks')->label('Görsel'),
                    TextInput::make('image_alt')->label('Görsel alternatif metni (ALT)')->maxLength(150),
                ]),
                self::button(),
            ],
            'features' => [
                ...self::heading(),
                Repeater::make('items')
                    ->label('Maddeler')
                    ->schema([
                        Select::make('icon')->label('İkon')->options(BlockRegistry::ICONS)->default('check-badge')->required(),
                        TextInput::make('title')->label('Başlık')->required()->maxLength(80),
                        Textarea::make('text')->label('Açıklama')->rows(2)->maxLength(300),
                    ])
                    ->columns(3)
                    ->reorderableWithDragAndDrop()
                    ->collapsible()
                    ->itemLabel(fn (array $state) => $state['title'] ?? null)
                    ->addActionLabel('Madde ekle')
                    ->maxItems(12),
            ],
            'stats' => [
                TextInput::make('title')->label('Başlık')->maxLength(150),
                Repeater::make('items')
                    ->label('İstatistikler')
                    ->helperText('Yalnızca doğrulanmış rakamları girin.')
                    ->schema([
                        TextInput::make('value')->label('Değer')->required()->maxLength(20),
                        TextInput::make('suffix')->label('Ek (ör. +, %)')->maxLength(5),
                        TextInput::make('label')->label('Açıklama')->required()->maxLength(60),
                    ])
                    ->columns(3)
                    ->reorderableWithDragAndDrop()
                    ->addActionLabel('İstatistik ekle')
                    ->maxItems(6),
            ],
            'promo_banners' => [
                TextInput::make('title')->label('Başlık')->maxLength(150),
                self::limit(2)->helperText('Bannerlar "Slider / Banner" modülünde "Ürün tanıtım bannerı" grubundan gelir.'),
            ],
            'whatsapp_cta' => [
                TextInput::make('title')->label('Başlık')->maxLength(150),
                Textarea::make('text')->label('Metin')->rows(2)->maxLength(300),
                TextInput::make('button_text')->label('Buton metni')->default('WhatsApp ile yazın')->maxLength(50),
                TextInput::make('prefill')->label('Hazır mesaj')->maxLength(200)
                    ->helperText('WhatsApp numarası Site Ayarları\'ndan alınır.'),
            ],
            'contact_cta' => [
                TextInput::make('title')->label('Başlık')->maxLength(150),
                Textarea::make('text')->label('Metin')->rows(2)->maxLength(300),
                self::button(),
                Toggle::make('show_phone')->label('Telefon numarasını göster')->default(true),
            ],
            'rich_text' => [
                TextInput::make('title')->label('Başlık')->maxLength(150),
                Fields::richText('content', 'blocks')->label('İçerik')->required(),
            ],
            'image' => [
                Fields::image('image', 'blocks')->label('Görsel')->required(),
                Grid::make(2)->schema([
                    TextInput::make('image_alt')->label('Alternatif metin (ALT)')->required()->maxLength(150),
                    TextInput::make('caption')->label('Alt yazı')->maxLength(200),
                ]),
                Fields::link('url', 'Tıklanınca gidilecek adres'),
            ],
        };
    }
}
