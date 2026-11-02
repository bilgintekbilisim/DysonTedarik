<?php

namespace App\Filament\Pages;

use App\Filament\Support\Fields;
use App\Filament\Support\SettingsPage;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Site Ayarları';

    protected static ?string $title = 'Site Ayarları';

    protected static ?string $slug = 'site-ayarlari';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function groups(): array
    {
        return ['general', 'contact', 'social', 'seo', 'integrations'];
    }

    protected function fields(): array
    {
        return [
            Tabs::make('settings')
                ->persistTabInQueryString()
                ->tabs([
                    Tab::make('Genel')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('general.site_name')->label('Site adı')->required()->maxLength(100),
                                TextInput::make('general.company_title')->label('Firma unvanı')->maxLength(200),
                            ]),
                            Grid::make(2)->schema([
                                Fields::image('general.logo', 'settings')->label('Logo (açık zemin)'),
                                Fields::image('general.logo_dark', 'settings')->label('Logo (koyu zemin)')
                                    ->helperText('Footer gibi lacivert alanlarda kullanılır.'),
                                Fields::image('general.favicon', 'settings')
                                    ->label('Favicon')
                                    ->acceptedFileTypes(['image/png'])
                                    ->maxSize(512)
                                    ->imageEditor(false)
                                    ->helperText('Kare PNG, en az 192×192 px, en fazla 512 KB.'),
                            ]),
                        ]),
                    Tab::make('İletişim')
                        ->icon('heroicon-o-phone')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('contact.phone')->label('Telefon')->tel()->maxLength(30),
                                TextInput::make('contact.phone_secondary')->label('İkinci telefon')->tel()->maxLength(30),
                                TextInput::make('contact.email')->label('E-posta')->email()->maxLength(150),
                                TextInput::make('contact.whatsapp')
                                    ->label('WhatsApp numarası')
                                    ->tel()
                                    ->regex('/^\+?[0-9 ]{10,16}$/')
                                    ->helperText('Ülke koduyla: +90 5xx xxx xx xx'),
                                TextInput::make('contact.quote_email')
                                    ->label('Teklif bildirim e-postası')
                                    ->email()
                                    ->helperText('Boşsa iletişim e-postasına gönderilir.'),
                                TextInput::make('contact.working_hours')->label('Çalışma saatleri')->maxLength(100),
                            ]),
                            Textarea::make('contact.address')->label('Adres')->rows(2)->maxLength(400),
                            TextInput::make('contact.maps_url')
                                ->label('Google Maps bağlantısı')
                                ->url()
                                ->maxLength(500)
                                ->helperText('Google Maps\'te "Paylaş" ile alınan bağlantı.'),
                            TextInput::make('contact.maps_embed_url')
                                ->label('Google Maps harita yerleştirme adresi')
                                ->url()
                                ->regex('#^https://www\.google\.com/maps/embed\?#')
                                ->maxLength(1000)
                                ->helperText('"Harita yerleştir" kodundaki yalnızca src="..." adresi (https://www.google.com/maps/embed?...).'),
                            TextInput::make('contact.header_note')
                                ->label('Üst bar notu')
                                ->maxLength(120)
                                ->helperText('Header\'daki iletişim şeridinde görünen kısa metin.'),
                        ]),
                    Tab::make('Sosyal medya')
                        ->icon('heroicon-o-share')
                        ->columns(2)
                        ->schema(collect([
                            'instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn',
                            'youtube' => 'YouTube', 'x' => 'X (Twitter)', 'tiktok' => 'TikTok',
                        ])->map(fn ($label, $key) => TextInput::make("social.{$key}")->label($label)->url()->maxLength(255))
                            ->values()->all()),
                    Tab::make('Varsayılan SEO')
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            TextInput::make('seo.default_title')->label('Varsayılan meta başlık')->maxLength(70),
                            TextInput::make('seo.title_suffix')
                                ->label('Başlık eki')
                                ->maxLength(40)
                                ->helperText('Tüm sayfa başlıklarının sonuna eklenir. Ör: " | Dyson Tedarik"'),
                            Textarea::make('seo.default_description')->label('Varsayılan meta açıklama')->rows(3)->maxLength(320),
                            Fields::image('seo.default_og_image', 'settings')->label('Varsayılan Open Graph görseli'),
                        ]),
                    Tab::make('Entegrasyonlar')
                        ->icon('heroicon-o-puzzle-piece')
                        ->schema([
                            TextInput::make('integrations.ga4_id')
                                ->label('Google Analytics 4 ölçüm kimliği')
                                ->regex('/^G-[A-Z0-9]{4,16}$/')
                                ->placeholder('G-XXXXXXXXXX'),
                            TextInput::make('integrations.gtm_id')
                                ->label('Google Tag Manager kimliği')
                                ->regex('/^GTM-[A-Z0-9]{4,12}$/')
                                ->placeholder('GTM-XXXXXXX'),
                            TextInput::make('integrations.google_site_verification')
                                ->label('Search Console doğrulama kodu')
                                ->regex('/^[A-Za-z0-9_-]{10,100}$/')
                                ->helperText('<meta name="google-site-verification" content="..."> içindeki yalnızca content değeri.'),
                            TextInput::make('integrations.bing_site_verification')
                                ->label('Bing Webmaster doğrulama kodu')
                                ->regex('/^[A-Za-z0-9]{10,64}$/'),
                        ]),
                ]),
        ];
    }
}
