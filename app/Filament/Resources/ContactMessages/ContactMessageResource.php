<?php

namespace App\Filament\Resources\ContactMessages;

use App\Enums\InquiryStatus;
use App\Filament\Resources\ContactMessages\Pages\ManageContactMessages;
use App\Filament\Support\InboxTable;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $slug = 'iletisim-mesajlari';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Talepler';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'İletişim Mesajları';

    protected static ?string $modelLabel = 'iletişim mesajı';

    protected static ?string $pluralModelLabel = 'İletişim Mesajları';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->where('status', InquiryStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(InboxTable::statusForm())->columns(1);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make('Gönderen')->columnSpanFull()->columns(3)->schema([
                TextEntry::make('full_name')->label('Ad soyad'),
                TextEntry::make('phone')->label('Telefon')->placeholder('—')->copyable(),
                TextEntry::make('email')->label('E-posta')->copyable()->url(fn (ContactMessage $r) => 'mailto:'.$r->email),
            ]),
            Section::make('Mesaj')->columnSpanFull()->schema([
                TextEntry::make('subject')->label('Konu')->placeholder('—'),
                TextEntry::make('message')->hiddenLabel(),
            ]),
            Section::make('Takip')->columnSpanFull()->columns(3)->schema([
                TextEntry::make('status')->label('Durum')->badge(),
                TextEntry::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i'),
                TextEntry::make('consent_at')->label('KVKK onayı')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('admin_note')->label('Yönetici notu')->placeholder('—')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return InboxTable::configure($table, [
            TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            TextColumn::make('full_name')->label('Ad soyad')->searchable(),
            TextColumn::make('subject')->label('Konu')->searchable()->limit(40)->placeholder('—'),
            TextColumn::make('email')->label('E-posta')->searchable()->toggleable(),
            TextColumn::make('status')->label('Durum')->badge(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageContactMessages::route('/'),
        ];
    }
}
