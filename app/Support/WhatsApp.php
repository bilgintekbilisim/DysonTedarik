<?php

namespace App\Support;

use App\Services\SettingsService;

class WhatsApp
{
    /** Site Ayarları'ndaki numarayla wa.me bağlantısı; numara girilmemişse null. */
    public static function link(?string $message = null): ?string
    {
        $number = preg_replace('/\D/', '', (string) app(SettingsService::class)->get('contact.whatsapp'));

        if ($number === '' || strlen($number) < 10) {
            return null;
        }

        // 05xx… ile girilmişse Türkiye kodunu ekle.
        if (str_starts_with($number, '0')) {
            $number = '90'.substr($number, 1);
        }

        return 'https://wa.me/'.$number.($message ? '?text='.rawurlencode($message) : '');
    }
}
