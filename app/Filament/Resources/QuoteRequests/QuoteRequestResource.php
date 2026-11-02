<?php

namespace App\Filament\Resources\QuoteRequests;

use App\Enums\InquiryStatus;
use App\Filament\Resources\QuoteRequests\Pages\ManageQuoteRequests;
use App\Filament\Support\InboxTable;
use App\Models\QuoteRequest;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class QuoteRequestResource extends Resource
{
    protected static ?string $model = QuoteRequest::class;

    protected static ?string $slug = 'teklif-talepleri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Talepler';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Teklif Talepleri';

    protected static ?string $modelLabel = 'teklif talebi';

    protected static ?string $pluralModelLabel = 'Teklif Talepleri';

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
            Section::make('Ürün')->schema([
                TextEntry::make('product_name')->label('Ürün adı')->placeholder('—'),
                TextEntry::make('product_code')->label('Ürün kodu')->placeholder('—')->copyable(),
                TextEntry::make('quantity')->label('Talep miktarı')->placeholder('—'),
            ]),
            Section::make('Talep eden')->schema([
                TextEntry::make('company_name')->label('Firma')->placeholder('—'),
                TextEntry::make('full_name')->label('Ad soyad'),
                TextEntry::make('phone')->label('Telefon')->copyable()->url(fn (QuoteRequest $r) => 'tel:'.preg_replace('/[^0-9+]/', '', $r->phone)),
                TextEntry::make('email')->label('E-posta')->copyable()->url(fn (QuoteRequest $r) => 'mailto:'.$r->email),
            ]),
            Section::make('Açıklama')->columnSpanFull()->schema([
                TextEntry::make('message')->hiddenLabel()->placeholder('Açıklama girilmedi.'),
            ]),
            Section::make('Takip')->columnSpanFull()->columns(3)->schema([
                TextEntry::make('status')->label('Durum')->badge(),
                TextEntry::make('created_at')->label('Talep tarihi')->dateTime('d.m.Y H:i'),
                TextEntry::make('consent_at')->label('KVKK onayı')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('admin_note')->label('Yönetici notu')->placeholder('—')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return InboxTable::configure($table, [
            TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            TextColumn::make('full_name')->label('Ad soyad')->searchable()->description(fn (QuoteRequest $r) => $r->company_name),
            TextColumn::make('product_name')->label('Ürün')->searchable()->limit(35)->description(fn (QuoteRequest $r) => $r->product_code),
            TextColumn::make('quantity')->label('Miktar')->placeholder('—'),
            TextColumn::make('phone')->label('Telefon')->searchable()->toggleable(),
            TextColumn::make('email')->label('E-posta')->searchable()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('status')->label('Durum')->badge(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageQuoteRequests::route('/'),
        ];
    }
}
