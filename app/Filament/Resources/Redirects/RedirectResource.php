<?php

namespace App\Filament\Resources\Redirects;

use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Filament\Support\Fields;
use App\Models\Redirect;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static ?string $slug = 'yonlendirmeler';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?string $navigationLabel = 'Yönlendirmeler (301)';

    protected static ?string $modelLabel = 'yönlendirme';

    protected static ?string $pluralModelLabel = 'Yönlendirmeler';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('from_path')
                ->label('Eski adres')
                ->required()
                ->maxLength(255)
                ->placeholder('/eski-urun-sayfasi')
                ->dehydrateStateUsing(fn (string $state) => Redirect::normalizePath($state))
                ->unique(ignoreRecord: true)
                ->notIn(['/', '/admin'])
                ->helperText('Yalnızca yol yazın; alan adı ve sorgu parametreleri otomatik temizlenir.'),
            Fields::link('to_url', 'Yeni adres')->required(),
            Select::make('status_code')
                ->label('Tür')
                ->options([301 => '301 — Kalıcı', 302 => '302 — Geçici'])
                ->default(301)
                ->selectablePlaceholder(false),
            Toggle::make('is_active')->label('Aktif')->default(true)->inline(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('from_path')->label('Eski adres')->searchable()->copyable(),
                TextColumn::make('to_url')->label('Yeni adres')->searchable()->limit(50),
                TextColumn::make('status_code')->label('Kod')->badge(),
                TextColumn::make('hits')->label('Kullanım')->numeric()->sortable(),
                TextColumn::make('last_hit_at')->label('Son kullanım')->since()->placeholder('—'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRedirects::route('/'),
        ];
    }
}
