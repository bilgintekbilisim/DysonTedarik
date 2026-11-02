<?php

namespace App\Filament\Resources\Menus\RelationManagers;

use App\Enums\MenuItemType;
use App\Filament\Support\Fields;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Menü öğeleri';

    protected static ?string $modelLabel = 'menü öğesi';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('label')
                    ->label('Görünen ad')
                    ->required()
                    ->maxLength(100),
                Select::make('parent_id')
                    ->label('Üst öğe')
                    ->placeholder('— Ana seviye —')
                    ->options(fn (?MenuItem $record) => $this->parentOptions($record))
                    ->helperText('Alt menü oluşturmak için bir üst öğe seçin (en fazla 3 seviye).'),
                Select::make('type')
                    ->label('Bağlantı türü')
                    ->options(MenuItemType::class)
                    ->default(MenuItemType::Url)
                    ->selectablePlaceholder(false)
                    ->required()
                    ->live(),
                Select::make('page_id')
                    ->label('Kurumsal sayfa')
                    ->options(fn () => Page::query()->corporate()->orderBy('title')->pluck('title', 'id'))
                    ->searchable()
                    ->required()
                    ->visible(fn (Get $get) => $this->typeIs($get, MenuItemType::Page)),
                Select::make('category_id')
                    ->label('Kategori')
                    ->options(fn () => Category::query()->with('parent')->orderBy('name')->get()->mapWithKeys(fn (Category $c) => [$c->id => $c->fullName()]))
                    ->searchable()
                    ->required()
                    ->visible(fn (Get $get) => $this->typeIs($get, MenuItemType::Category)),
                Select::make('brand_id')
                    ->label('Marka')
                    ->options(fn () => Brand::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->visible(fn (Get $get) => $this->typeIs($get, MenuItemType::Brand)),
                Select::make('route_name')
                    ->label('Sistem sayfası')
                    ->options(MenuItemType::routeOptions())
                    ->required()
                    ->visible(fn (Get $get) => $this->typeIs($get, MenuItemType::Route)),
                Fields::link('url', 'Adres')
                    ->required()
                    ->visible(fn (Get $get) => $this->typeIs($get, MenuItemType::Url)),
            ]),
            Grid::make(3)->schema([
                Toggle::make('open_in_new_tab')->label('Yeni sekmede aç'),
                Toggle::make('is_mega')
                    ->label('Mega menü')
                    ->helperText('Masaüstünde alt kategorileri geniş panelde gösterir (yalnızca üst menü, ana seviye).'),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['parent', 'page', 'category', 'brand']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('label')
                    ->label('Öğe')
                    ->formatStateUsing(fn (MenuItem $record, string $state) => str_repeat('— ', $this->depth($record)).$state)
                    ->searchable(),
                TextColumn::make('parent.label')->label('Üst öğe')->placeholder('Ana seviye'),
                TextColumn::make('type')->label('Tür')->badge()->color('gray'),
                TextColumn::make('target')
                    ->label('Hedef')
                    ->state(fn (MenuItem $record) => $record->resolveUrl() ?? 'Hedef bulunamadı / yayında değil')
                    ->limit(40)
                    ->color(fn (MenuItem $record) => $record->resolveUrl() ? null : 'danger'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Üst öğe')
                    ->options(fn () => $this->getOwnerRecord()->items()->whereNull('parent_id')->pluck('label', 'id')),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    private function typeIs(Get $get, MenuItemType $type): bool
    {
        $value = $get('type');

        return ($value instanceof MenuItemType ? $value : MenuItemType::tryFrom((string) $value)) === $type;
    }

    /** Kendisi ve alt öğeleri hariç, en fazla 2. seviyedeki öğeler üst öğe olabilir. */
    private function parentOptions(?Model $record): array
    {
        $items = $this->getOwnerRecord()->items()->get();
        $excluded = $record ? $this->descendantIds($items, $record->getKey()) : [];

        return $items
            ->reject(fn (MenuItem $item) => in_array($item->id, $excluded, true) || $this->depth($item) >= 2)
            ->mapWithKeys(fn (MenuItem $item) => [$item->id => str_repeat('— ', $this->depth($item)).$item->label])
            ->all();
    }

    private function descendantIds($items, int $id): array
    {
        $ids = [$id];

        foreach ($items->where('parent_id', $id) as $child) {
            $ids = [...$ids, ...$this->descendantIds($items, $child->id)];
        }

        return $ids;
    }

    private function depth(MenuItem $item): int
    {
        $depth = 0;
        $current = $item;

        while ($current->parent_id && $depth < 5) {
            $current = $current->parent;
            $depth++;
        }

        return $depth;
    }
}
