<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Support\Fields;
use App\Models\AttributeValue;
use App\Models\Category;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Tabs::make('product')
                    ->columnSpan(2)
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Genel')->icon('heroicon-o-information-circle')->schema(self::general()),
                        Tab::make('Açıklama')->icon('heroicon-o-document-text')->schema(self::content()),
                        Tab::make('Teknik bilgiler')->icon('heroicon-o-wrench-screwdriver')->schema(self::technical()),
                        Tab::make('Görseller ve dokümanlar')->icon('heroicon-o-photo')->schema(self::media()),
                    ]),
                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Yayın')->schema([
                            Toggle::make('is_active')->label('Aktif')->default(true),
                            Toggle::make('is_featured')->label('Öne çıkan ürün')->helperText('Ana sayfadaki "Öne çıkan ürünler" bloğunda gösterilir.'),
                            DateTimePicker::make('published_at')
                                ->label('Yayın tarihi')
                                ->native(false)
                                ->seconds(false)
                                ->helperText('Boşsa hemen yayında.'),
                            TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                        ]),
                        Section::make('Kategori ve marka')->schema([
                            Select::make('category_id')
                                ->label('Ana kategori')
                                ->options(fn () => self::categoryOptions())
                                ->searchable()
                                ->required(),
                            Select::make('categories')
                                ->label('Ek kategoriler')
                                ->relationship('categories', 'name', fn (Builder $query) => $query->with('parent.parent')->orderBy('name'))
                                ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->fullName())
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->helperText('Ürün bu kategorilerde de listelenir.'),
                            Select::make('brand_id')
                                ->label('Marka')
                                ->relationship('brand', 'name', fn (Builder $query) => $query->orderBy('name'))
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    TextInput::make('name')->label('Marka adı')->required()->maxLength(255),
                                ]),
                        ]),
                    ]),
                Fields::seoSection('products/seo')->columnSpanFull(),
            ]);
    }

    private static function general(): array
    {
        return [
            Fields::titleWithSlug('name', 'Ürün adı'),
            Grid::make(2)->schema([
                TextInput::make('code')
                    ->label('Ürün kodu')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->regex('/^[\pL\pN._\/-]+$/u')
                    ->validationMessages(['regex' => 'Ürün kodu yalnızca harf, rakam ve . _ - / karakterlerini içerebilir.'])
                    ->helperText('Benzersiz olmalı; Excel aktarımında eşleştirme anahtarıdır.'),
                Fields::slug(),
            ]),
            Textarea::make('short_description')
                ->label('Kısa açıklama')
                ->rows(3)
                ->maxLength(500)
                ->helperText('Ürün kartlarında ve detay sayfasının üstünde görünür.'),
        ];
    }

    private static function content(): array
    {
        return [
            Fields::richText('description', 'products')->label('Detaylı açıklama'),
            Fields::richText('usage_areas', 'products')->label('Kullanım alanları'),
        ];
    }

    private static function technical(): array
    {
        return [
            Grid::make(3)->schema([
                TextInput::make('color')->label('Renk')->maxLength(255)->placeholder('Beyaz, Şeffaf, Gri'),
                TextInput::make('volume')->label('Hacim / ağırlık')->maxLength(255)->placeholder('310 ml'),
                TextInput::make('packaging')->label('Ambalaj')->maxLength(255)->placeholder('Koli içi 24 adet'),
            ]),
            Repeater::make('technical_specs')
                ->label('Teknik özellikler tablosu')
                ->schema([
                    TextInput::make('label')->label('Özellik')->required()->maxLength(100),
                    TextInput::make('value')->label('Değer')->required()->maxLength(255),
                ])
                ->columns(2)
                ->reorderableWithDragAndDrop()
                ->addActionLabel('Satır ekle')
                ->defaultItems(0)
                ->helperText('Ürün detayında tablo olarak gösterilir (ör. Kür süresi — 24 saat).'),
            Select::make('attributeValues')
                ->label('Filtre özellikleri')
                ->relationship(
                    'attributeValues',
                    'value',
                    fn (Builder $query) => $query->with('attribute')->orderBy('attribute_id')->orderBy('sort_order'),
                )
                ->getOptionLabelFromRecordUsing(fn (AttributeValue $record) => "{$record->attribute->name}: {$record->value}")
                ->multiple()
                ->preload()
                ->searchable()
                ->helperText('Kategori sayfasındaki filtrelerde kullanılır. Yeni değerleri "Ürün Özellikleri" menüsünden ekleyin.'),
        ];
    }

    private static function media(): array
    {
        return [
            Repeater::make('images')
                ->label('Ürün görselleri')
                ->relationship()
                ->orderColumn('sort_order')
                ->reorderableWithDragAndDrop()
                ->schema([
                    Fields::image('path', 'products')->label('Görsel')->required(),
                    TextInput::make('alt')
                        ->label('Alternatif metin (ALT)')
                        ->maxLength(150)
                        ->helperText('Boşsa ürün adı kullanılır.'),
                ])
                ->columns(2)
                ->grid(2)
                ->addActionLabel('Görsel ekle')
                ->defaultItems(0)
                ->maxItems(20)
                ->helperText('İlk görsel kapak görseli olarak kullanılır; sürükleyerek sırayı değiştirin.'),
            Repeater::make('documents')
                ->label('Teknik dokümanlar (PDF)')
                ->relationship()
                ->orderColumn('sort_order')
                ->reorderableWithDragAndDrop()
                ->schema([
                    TextInput::make('title')->label('Doküman adı')->required()->maxLength(150)->placeholder('Teknik Bilgi Formu'),
                    Fields::pdf('path', 'products/documents')->label('PDF dosyası')->required(),
                ])
                ->columns(2)
                ->addActionLabel('Doküman ekle')
                ->defaultItems(0)
                ->maxItems(10),
        ];
    }

    /** Kategoriler "Ana › Alt › Alt-alt" yolu ile listelenir. */
    private static function categoryOptions(): array
    {
        // En alt seviye de seçilebilsin diye parentOptions'ın derinlik sınırı kullanılmaz.
        $categories = Category::query()->get(['id', 'parent_id', 'name'])->keyBy('id');

        return $categories
            ->mapWithKeys(function (Category $category) use ($categories) {
                $names = [$category->name];
                $current = $category;

                while ($current->parent_id && $categories->has($current->parent_id) && count($names) < CategoryForm::MAX_DEPTH + 2) {
                    $current = $categories[$current->parent_id];
                    array_unshift($names, $current->name);
                }

                return [$category->id => implode(' › ', $names)];
            })
            ->sort()
            ->all();
    }
}
