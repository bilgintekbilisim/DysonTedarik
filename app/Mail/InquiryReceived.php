<?php

namespace App\Mail;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\QuoteRequests\QuoteRequestResource;
use App\Models\ContactMessage;
use App\Models\QuoteRequest;
use App\Services\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Yöneticiye giden "yeni teklif talebi / iletişim mesajı" bildirimi. */
class InquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public QuoteRequest|ContactMessage $inquiry) {}

    public function envelope(): Envelope
    {
        $site = app(SettingsService::class)->get('general.site_name', config('app.name'));
        $isQuote = $this->inquiry instanceof QuoteRequest;

        // Konu satırına ziyaretçi girdisi kısaltılarak eklenir; satır sonu içeremez.
        $detail = $isQuote ? $this->inquiry->product_name : ($this->inquiry->subject ?: $this->inquiry->full_name);
        $detail = str((string) $detail)->replaceMatches('/[\r\n]+/', ' ')->limit(60)->value();

        return new Envelope(
            replyTo: [new Address($this->inquiry->email, $this->inquiry->full_name)],
            subject: ($isQuote ? 'Yeni teklif talebi' : 'Yeni iletişim mesajı')." — {$detail} | {$site}",
        );
    }

    public function content(): Content
    {
        $isQuote = $this->inquiry instanceof QuoteRequest;

        return new Content(
            markdown: 'mail.inquiry-received',
            with: [
                'isQuote' => $isQuote,
                'rows' => array_map(fn ($value) => self::plain((string) $value), array_filter($isQuote ? [
                    'Ürün' => $this->inquiry->product_name,
                    'Ürün kodu' => $this->inquiry->product_code,
                    'Talep miktarı' => $this->inquiry->quantity,
                    'Firma' => $this->inquiry->company_name,
                    'Ad soyad' => $this->inquiry->full_name,
                    'Telefon' => $this->inquiry->phone,
                    'E-posta' => $this->inquiry->email,
                ] : [
                    'Ad soyad' => $this->inquiry->full_name,
                    'Telefon' => $this->inquiry->phone,
                    'E-posta' => $this->inquiry->email,
                    'Konu' => $this->inquiry->subject,
                ])),
                'body' => $this->inquiry->message ? str_replace(["\r\n", "\n", "\r"], '<br>', self::plain($this->inquiry->message)) : null,
                'panelUrl' => $isQuote ? QuoteRequestResource::getUrl('index') : ContactMessageResource::getUrl('index'),
            ],
        );
    }

    /**
     * Ziyaretçi metnini HTML ve Markdown olarak yorumlanmayacak hâle getirir
     * (ör. "[tıklayın](https://...)" e-postada bağlantıya dönüşmesin).
     */
    public static function plain(string $value): string
    {
        return strtr(e($value), [
            '[' => '&#91;', ']' => '&#93;', '*' => '&#42;', '_' => '&#95;', '`' => '&#96;',
            '|' => '&#124;', '#' => '&#35;', '!' => '&#33;',
        ]);
    }
}
