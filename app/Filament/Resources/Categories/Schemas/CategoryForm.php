<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Support\Fields;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    /** En fazla 3 seviye: Ana kategori › Alt kategori › Alt-alt kategori */
    public const MAX_DEPTH = 3;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Kategori')
                    ->columnSpan(2)
                    ->schema([
                        Fields::titleWithSlug('name', 'Kategori adı'),
                        Fields::slug(),
                        Select::make('parent_id')
                            ->label('Üst kategori')
                            ->placeholder('— Ana kategori —')
                            ->options(fn (?Category $record) => self::parentOptions($record))
                            ->searchable(),
                        Textarea::make('summary')
                            ->label('Kısa açıklama')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Kategori kartlarında ve mega menüde görünür.'),
                        Fields::richText('description', 'categories')
                            ->label('Kategori açıklaması')
                            ->helperText('Kategori sayfasında ürün listesinin altında gösterilir; SEO için önemlidir.'),
                    ]),
                Section::make('Görünüm')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')->label('Aktif')->default(true),
                        Toggle::make('show_in_menu')->label('Menüde göster')->default(true),
                        TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                        Fields::image('image', 'categories')->label('Kategori görseli'),
                    ]),
                Fields::seoSection('categories/seo')->columnSpanFull(),
            ]);
    }

    /**
     * Kendisi ve alt kategorileri üst kategori olarak seçilemez (döngü oluşmasın);
     * en alt seviyedeki kategoriler de üst kategori olamaz.
     */
    public static function parentOptions(?Category $record): array
    {
        $categories = Category::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'parent_id', 'name'])->keyBy('id');
        $excluded = $record ? Category::descendantIdsOf($record->id) : [];

        $depth = function (Category $category) use ($categories): int {
            $level = 1;

            while ($category->parent_id && $categories->has($category->parent_id) && $level < 10) {
                $category = $categories[$category->parent_id];
                $level++;
            }

            return $level;
        };

        $path = function (Category $category) use ($categories): string {
            $names = [$category->name];

            while ($category->parent_id && $categories->has($category->parent_id) && count($names) < 10) {
                $category = $categories[$category->parent_id];
                array_unshift($names, $category->name);
            }

            return implode(' › ', $names);
        };

        return $categories
            ->reject(fn (Category $c) => in_array($c->id, $excluded, true) || $depth($c) >= self::MAX_DEPTH)
            ->mapWithKeys(fn (Category $c) => [$c->id => $path($c)])
            ->sort()
            ->all();
    }
}
