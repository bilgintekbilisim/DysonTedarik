<?php

namespace App\Actions;

use App\Enums\InquiryStatus;
use App\Mail\InquiryReceived;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Teklif talebini veya iletişim mesajını kaydeder ve yöneticiye e-posta bildirimi gönderir.
 * E-posta yanıt gönderildikten sonra (defer) iletilir: SMTP yavaşsa ziyaretçi beklemez,
 * SMTP hatası kaydı kaybettirmez — kayıt panelde her zaman görünür.
 */
class SubmitInquiry
{
    public function __construct(private SettingsService $settings) {}

    public function quote(array $data, Request $request): QuoteRequest
    {
        $product = filled($data['product_code'] ?? null)
            ? Product::query()->active()->where('code', $data['product_code'])->first()
            : null;

        $quote = QuoteRequest::query()->create([
            ...collect($data)->only(['product_name', 'product_code', 'quantity', 'company_name', 'full_name', 'phone', 'email', 'message'])->all(),
            'product_id' => $product?->id,
            ...$this->meta($request),
        ]);

        $this->notify($quote, $this->settings->get('contact.quote_email') ?: $this->settings->get('contact.email'));

        return $quote;
    }

    public function contact(array $data, Request $request): ContactMessage
    {
        $message = ContactMessage::query()->create([
            ...collect($data)->only(['full_name', 'email', 'phone', 'subject', 'message'])->all(),
            ...$this->meta($request),
        ]);

        $this->notify($message, $this->settings->get('contact.email'));

        return $message;
    }

    private function meta(Request $request): array
    {
        return [
            'status' => InquiryStatus::New,
            'consent_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ];
    }

    private function notify(Model $inquiry, ?string $recipient): void
    {
        $recipient = $recipient ?: config('mail.from.address');

        if (blank($recipient)) {
            return;
        }

        defer(function () use ($inquiry, $recipient) {
            try {
                Mail::to($recipient)->send(new InquiryReceived($inquiry));
            } catch (Throwable $e) {
                Log::warning('Talep bildirimi e-postası gönderilemedi.', [
                    'type' => class_basename($inquiry),
                    'id' => $inquiry->getKey(),
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
