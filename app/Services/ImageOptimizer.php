<?php

namespace App\Services;

use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Yüklenen JPG/PNG/WEBP görselleri WebP'ye çevirir ve en uzun kenarı sınırlar; şeffaflık korunur.
 * Ek kütüphane gerektirmez (PHP GD). İşlem başarısız olursa orijinal dosya olduğu gibi kalır.
 */
class ImageOptimizer
{
    public const QUALITY = 82;

    private const TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** @return string yeni (veya değişmemiş) dosya yolu */
    public function optimize(string $path, int $maxSize): string
    {
        $disk = Storage::disk('public');
        $webp = preg_replace('/\.(jpe?g|png|webp)$/i', '', $path).'.webp';

        // Form, daha önce WebP'ye çevrilmiş dosyanın eski adını yeniden gönderirse çevrilmiş dosyayı kullan.
        if (! $disk->exists($path)) {
            return $disk->exists($webp) ? $webp : $path;
        }

        if (! in_array($disk->mimeType($path), self::TYPES, true)) {
            return $path;
        }

        try {
            $image = $this->load($disk->path($path));

            if (! $image) {
                return $path;
            }

            $isWebp = str_ends_with(strtolower($path), '.webp');
            $tooLarge = max(imagesx($image), imagesy($image)) > $maxSize;

            // Zaten küçük bir WebP ise yeniden sıkıştırıp kaliteyi düşürme.
            if ($isWebp && ! $tooLarge) {
                return $path;
            }

            imagewebp($this->resize($image, $maxSize), $disk->path($webp), self::QUALITY);

            if ($webp !== $path) {
                $disk->delete($path);
            }

            return $webp;
        } catch (Throwable $e) {
            Log::warning('Görsel optimize edilemedi', ['path' => $path, 'error' => $e->getMessage()]);

            return $path;
        }
    }

    /** "products/a.webp" için "products/a-480.webp" küçük görselini üretir. */
    public function thumbnail(string $path, int $width = 480): ?string
    {
        $disk = Storage::disk('public');
        $target = self::thumbnailPath($path, $width);

        if (! $disk->exists($path)) {
            return null;
        }

        try {
            $image = $this->load($disk->path($path));

            if (! $image) {
                return null;
            }

            imagewebp($this->resize($image, $width), $disk->path($target), self::QUALITY);

            return $target;
        } catch (Throwable $e) {
            Log::warning('Küçük görsel üretilemedi', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public static function thumbnailPath(string $path, int $width = 480): string
    {
        return preg_replace('/\.[a-z0-9]+$/i', '', $path)."-{$width}.webp";
    }

    private function load(string $file): ?GdImage
    {
        $image = match (@getimagesize($file)[2] ?? null) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($file),
            IMAGETYPE_PNG => imagecreatefrompng($file),
            IMAGETYPE_WEBP => imagecreatefromwebp($file),
            default => false,
        };

        if (! $image) {
            return null;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }

    private function resize(GdImage $image, int $maxSize): GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $scale = min(1, $maxSize / max($w, $h));

        if ($scale >= 1) {
            return $image;
        }

        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $out = imagecreatetruecolor($nw, $nh);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagecopyresampled($out, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $out;
    }
}
