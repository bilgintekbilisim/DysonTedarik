<?php

namespace App\Filament\Pages;

use App\Filament\Support\Fields;
use App\Filament\Support\SettingsPage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Throwable;
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
        return ['general', 'contact', 'company', 'social', 'seo', 'integrations'];
    }

    /** SMTP bilgileri .env'de tutulur; burada yalnızca çalışıp çalışmadığı denenir. */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('testMail')
                ->label('Test e-postası gönder')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(fn () => 'Sunucudaki SMTP ayarlarıyla '.auth()->user()->email.' adresine test e-postası gönderilecek.')
                ->action(function () {
                    try {
                        Mail::raw(
                            'Bu e-posta, Dyson Tedarik yönetim panelindeki SMTP testi ile gönderildi. Bu iletiyi aldıysanız teklif ve iletişim bildirimleri de ulaşacaktır.',
                            fn ($message) => $message->to(auth()->user()->email)->subject('SMTP test e-postası'),
                        );
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title('E-posta gönderilemedi')
                            ->body('Sunucudaki MAIL_* ayarlarını kontrol edin. Hata: '.str($e->getMessage())->limit(200))
                            ->persistent()->send();

                        return;
                    }

                    $mailer = config('mail.default');
                    Notification::make()->success()->title('Test e-postası gönderildi')
                        ->body(in_array($mailer, ['log', 'array'], true)
                            ? "Not: Gönderim yöntemi \"{$mailer}\"; e-posta gerçekte gönderilmedi, yalnızca kaydedildi."
                            : 'Gelen kutunuzu (ve istenmeyen klasörünü) kontrol edin.')
                        ->send();
                }),
        ];
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
                                TextInput::make('contact.phone')->label('Telefon')->tel()->maxLength(30)
                                    ->helperText('Header, footer ve "Ara" butonlarında kullanılır.'),
                                TextInput::make('contact.phone_label')->label('Telefon etiketi')->maxLength(40)->placeholder('Satış / Yetkili'),
                                TextInput::make('contact.phone_secondary')->label('İkinci telefon')->tel()->maxLength(30),
                                TextInput::make('contact.phone_secondary_label')->label('İkinci telefon etiketi')->maxLength(40)->placeholder('Muhasebe'),
                                TextInput::make('contact.email')->label('E-posta')->email()->maxLength(150),
                                TextInput::make('contact.email_secondary')->label('İkinci e-posta')->email()->maxLength(150),
                                TextInput::make('contact.email_secondary_label')->label('İkinci e-posta etiketi')->maxLength(40)->placeholder('Muhasebe'),
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
                            Textarea::make('contact.address')->label('Adres (mahalle, sokak, no)')->rows(2)->maxLength(400),
                            Grid::make(3)->schema([
                                TextInput::make('contact.district')->label('İlçe')->maxLength(60),
                                TextInput::make('contact.city')->label('İl')->maxLength(60),
                                TextInput::make('contact.postal_code')->label('Posta kodu')->regex('/^[0-9]{5}$/')->maxLength(5),
                            ]),
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
                    Tab::make('Firma bilgileri')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Text::make('Ticari iletişim kuralları gereği footer ve iletişim sayfasında gösterilir. Boş bırakılan alan gösterilmez.'),
                            Grid::make(2)->schema([
                                TextInput::make('company.tax_office')->label('Vergi dairesi')->maxLength(80),
                                TextInput::make('company.tax_number')->label('Vergi numarası')->regex('/^[0-9 ]{10,13}$/')->maxLength(13),
                                TextInput::make('company.mersis')->label('MERSİS no')->regex('/^[0-9]{16}$/')
                                    ->validationMessages(['regex' => 'MERSİS numarası 16 haneli olmalıdır.']),
                                TextInput::make('company.trade_registry')->label('Ticaret sicil no')->maxLength(30),
                                TextInput::make('company.website')->label('Web sitesi')->url()->maxLength(150),
                            ]),
                            Toggle::make('company.show_in_footer')->label('Yasal bilgileri footer\'da göster')->default(true),
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
