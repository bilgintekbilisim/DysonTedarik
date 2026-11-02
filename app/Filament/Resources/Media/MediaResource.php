<?php

namespace App\Filament\Resources\Media;

use App\Filament\Resources\Media\Pages\ManageMedia;
use App\Filament\Support\Fields;
use App\Models\Media;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use UnitEnum;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static ?string $slug = 'medya';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Medya Kütüphanesi';

    protected static ?string $modelLabel = 'dosya';

    protected static ?string $pluralModelLabel = 'Medya Kütüphanesi';

    public const FOLDERS = [
        'genel' => 'Genel',
        'kurumsal' => 'Kurumsal',
        'urun' => 'Ürün',
        'banner' => 'Banner',
        'dokuman' => 'Doküman',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('path')
                ->label('Dosya')
                ->disk('public')
                ->directory('media')
                ->visibility('public')
                ->acceptedFileTypes([...Fields::IMAGE_TYPES, 'application/pdf'])
                ->maxSize(Fields::PDF_MAX_KB)
                ->storeFileNamesIn('original_name')
                ->required()
                ->disabled(fn (string $operation) => $operation === 'edit')
                ->helperText('JPG, PNG, WEBP veya PDF — en fazla 20 MB.')
                ->columnSpanFull(),
            TextInput::make('alt')
                ->label('Alternatif metin (ALT)')
                ->maxLength(150),
            Select::make('folder')
                ->label('Klasör')
                ->options(self::FOLDERS)
                ->default('genel')
                ->selectablePlaceholder(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('path')
                    ->label('Önizleme')
                    ->disk('public')
                    ->imageHeight(48),
                TextColumn::make('original_name')
                    ->label('Dosya adı')
                    ->searchable()
                    ->limit(40)
                    ->description(fn (Media $record) => $record->alt),
                TextColumn::make('folder')
                    ->label('Klasör')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::FOLDERS[$state] ?? $state),
                TextColumn::make('mime_type')->label('Tür')->toggleable(),
                TextColumn::make('size')
                    ->label('Boyut')
                    ->formatStateUsing(fn ($state) => Number::fileSize((int) $state, precision: 1)),
                TextColumn::make('created_at')->label('Yüklenme')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('folder')->label('Klasör')->options(self::FOLDERS),
            ])
            ->recordActions([
                Action::make('copyUrl')
                    ->label('Adresi kopyala')
                    ->icon('heroicon-o-clipboard')
                    ->color('gray')
                    ->alpineClickHandler(fn (Media $record) => 'window.navigator.clipboard.writeText('.json_encode($record->url()).'); $tooltip(\'Kopyalandı\')'),
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
            'index' => ManageMedia::route('/'),
        ];
    }
}
