<?php

namespace App\Filament\Resources\Media;

use App\Filament\Resources\Media\Pages\ManageMedia;
use App\Filament\Support\Fields;
use App\Models\Media;
use App\Services\MediaLibrary;
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
                ImageColumn::make('preview')
                    ->label('Önizleme')
                    ->disk('public')
                    ->imageHeight(48)
                    ->getStateUsing(fn (Media $record) => $record->isImage() ? $record->path : null)
                    ->defaultImageUrl(fn (Media $record) => $record->isImage() ? null : 'data:image/svg+xml;utf8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><rect width="48" height="48" rx="6" fill="#fee2e2"/><text x="24" y="30" font-family="Arial" font-size="13" font-weight="700" fill="#b91c1c" text-anchor="middle">PDF</text></svg>')),
                TextColumn::make('original_name')
                    ->label('Dosya adı')
                    ->searchable(['original_name', 'path', 'alt'])
                    ->limit(40)
                    ->description(fn (Media $record) => $record->path),
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
                SelectFilter::make('type')
                    ->label('Tür')
                    ->options(['image' => 'Görsel', 'pdf' => 'PDF'])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'image' => $query->where('mime_type', 'like', 'image/%'),
                        'pdf' => $query->where('mime_type', 'application/pdf'),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('copyUrl')
                    ->label('Adresi kopyala')
                    ->icon('heroicon-o-clipboard')
                    ->color('gray')
                    ->alpineClickHandler(fn (Media $record) => 'window.navigator.clipboard.writeText('.json_encode($record->url()).'); $tooltip(\'Kopyalandı\')'),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(function (Media $record) {
                        $usages = app(MediaLibrary::class)->usages($record->path);

                        return $usages
                            ? 'Bu dosya şu yerlerde kullanılıyor ve oradan da kaldırılacak: '.implode(', ', $usages).'. Zorunlu görsel/dosya olarak kullanıldığı kayıt (ürün görseli, slayt, e-katalog) silinir. Bu işlem geri alınamaz.'
                            : 'Dosya sunucudan kalıcı olarak silinecek. Bu işlem geri alınamaz.';
                    }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->modalDescription('Seçilen dosyalar sunucudan ve kullanıldıkları yerlerden (ürün görselleri, marka logoları, slaytlar, kataloglar) kalıcı olarak silinecek.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMedia::route('/'),
        ];
    }
}
