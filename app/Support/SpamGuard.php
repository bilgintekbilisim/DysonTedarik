<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Captcha gerektirmeyen bot koruması:
 *  - honeypot: insanların görmediği "web_sitesi" alanı dolu gelirse bot,
 *  - zaman tuzağı: form, sayfa açıldıktan MIN_SECONDS içinde gönderilirse veya
 *    şifreli zaman damgası yoksa/bozuksa bot.
 * Bot tespit edilince kullanıcıya hata gösterilmez; kayıt sessizce atılır.
 */
class SpamGuard
{
    public const HONEYPOT = 'web_sitesi';

    public const TIMESTAMP = '_ts';

    public const MIN_SECONDS = 3;

    public const MAX_SECONDS = 7200;

    public static function token(): string
    {
        return Crypt::encryptString((string) now()->timestamp);
    }

    public static function isSpam(Request $request): bool
    {
        if (filled($request->input(self::HONEYPOT))) {
            return true;
        }

        try {
            $issuedAt = (int) Crypt::decryptString((string) $request->input(self::TIMESTAMP));
        } catch (DecryptException) {
            return true;
        }

        $elapsed = now()->timestamp - $issuedAt;

        return $elapsed < self::MIN_SECONDS || $elapsed > self::MAX_SECONDS;
    }
}
