<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'kullanicilar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Kullanıcılar ve Roller';

    protected static ?string $modelLabel = 'kullanıcı';

    protected static ?string $pluralModelLabel = 'Kullanıcılar';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Ad soyad')->required()->maxLength(100),
            TextInput::make('email')->label('E-posta')->email()->required()->maxLength(150)->unique(ignoreRecord: true),
            Select::make('role')
                ->label('Rol')
                ->options(UserRole::class)
                ->default(UserRole::Editor)
                ->selectablePlaceholder(false)
                ->required()
                ->helperText('Editör: içerik, katalog, menü ve talepleri yönetir. Yönetici: ek olarak site ayarları ve kullanıcılar.')
                // Kişi kendi rolünü düşürüp panelde yöneticisiz kalınmasına yol açmasın.
                ->disabled(fn (?User $record) => $record?->is(auth()->user())),
            Toggle::make('is_active')
                ->label('Hesap aktif')
                ->default(true)
                ->inline(false)
                ->disabled(fn (?User $record) => $record?->is(auth()->user())),
            TextInput::make('password')
                ->label('Şifre')
                ->password()
                ->revealable()
                ->rule(Password::min(10)->letters()->numbers())
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Değiştirmek istemiyorsanız boş bırakın.' : 'En az 10 karakter, harf ve rakam içermeli.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Ad soyad')->searchable(),
                TextColumn::make('email')->label('E-posta')->searchable(),
                TextColumn::make('role')->label('Rol')->badge(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('last_login_at')->label('Son giriş')->since()->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
