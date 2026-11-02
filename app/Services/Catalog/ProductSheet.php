<?php

namespace App\Services\Catalog;

use Illuminate\Support\Str;

/**
 * Ürün Excel/CSV dosyalarının sütun sözleşmesi. Dışa aktarılan dosya değiştirilmeden
 * geri içe aktarılabilir (aynı başlıklar, aynı hücre biçimleri).
 */
class ProductSheet
{
    /** Liste hücrelerinde öğe ayırıcı: "Silikon | Mastik" */
    public const LIST_SEPARATOR = '|';

    /** Kategori yolu ayırıcı: "Yapı Kimyasalları > Silikonlar" */
    public const PATH_SEPARATOR = '>';

    /** başlık => açıklama (şablon dosyasındaki açıklama sayfasında gösterilir) */
    public const COLUMNS = [
        'kod' => 'Zorunlu. Benzersiz ürün kodu; mevcut ürün bu kodla bulunup güncellenir.',
        'ad' => 'Yeni ürün için zorunlu. Ürün adı.',
        'marka' => 'Marka adı. Yoksa oluşturulur (seçenek açıksa).',
        'ana_kategori' => 'Kategori yolu: Yapı Kimyasalları > Silikonlar. Yoksa oluşturulur (seçenek açıksa).',
        'ek_kategoriler' => 'Birden fazla yol, | ile ayrılır: Silikonlar | Mastikler > Akrilik',
        'kisa_aciklama' => 'En fazla 500 karakter.',
        'aciklama' => 'Detaylı açıklama. HTML kullanılabilir; güvensiz etiketler temizlenir.',
        'kullanim_alanlari' => 'HTML kullanılabilir.',
        'teknik_ozellikler' => 'Özellik: Değer çiftleri, | ile ayrılır: Kür süresi: 24 saat | Sıcaklık: -40/+120 °C',
        'renk' => 'Serbest metin.',
        'hacim' => 'Serbest metin (310 ml, 25 kg).',
        'ambalaj' => 'Serbest metin.',
        'filtre_ozellikleri' => 'Özellik=Değer çiftleri, | ile ayrılır: Renk=Beyaz | Hacim=310 ml',
        'aktif' => 'evet / hayır (boşsa yeni ürün aktif olur).',
        'one_cikan' => 'evet / hayır',
        'sira' => 'Sayı.',
        'seo_baslik' => 'En fazla 70 karakter.',
        'seo_aciklama' => 'En fazla 320 karakter.',
    ];

    /** "Ürün Kodu", "KOD ", "kod" gibi başlıkları sözleşmedeki anahtara çevirir. */
    public static function normalizeHeader(?string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);

        return Str::slug(trim($header), '_', 'tr');
    }

    /** @return array<int, string> */
    public static function splitList(?string $value): array
    {
        return collect(explode(self::LIST_SEPARATOR, (string) $value))
            ->map(fn (string $item) => trim($item))
            ->filter(fn (string $item) => $item !== '')
            ->values()
            ->all();
    }

    /** @return array<int, string> "A > B › C" → ["A", "B", "C"] */
    public static function splitPath(string $path): array
    {
        return collect(preg_split('/\s*[>›]\s*/u', trim($path)))
            ->filter(fn (string $part) => $part !== '')
            ->values()
            ->all();
    }

    /** "evet", "1", "true", "e", "yes", "x" → true; "hayır", "0", "false", "h", "no" → false; tanınmayan → null */
    public static function parseBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = Str::lower(trim((string) $value));

        return match (true) {
            in_array($normalized, ['1', 'evet', 'e', 'true', 'yes', 'x', 'aktif'], true) => true,
            in_array($normalized, ['0', 'hayır', 'hayir', 'h', 'false', 'no', 'pasif'], true) => false,
            default => null,
        };
    }

    public static function formatBool(bool $value): string
    {
        return $value ? 'evet' : 'hayır';
    }

    /** @return array<int, array{label: string, value: string}> */
    public static function parseSpecs(string $value): array
    {
        return collect(self::splitList($value))
            ->map(function (string $pair) {
                [$label, $specValue] = array_pad(explode(':', $pair, 2), 2, '');

                return ['label' => trim($label), 'value' => trim($specValue)];
            })
            ->filter(fn (array $spec) => $spec['label'] !== '' && $spec['value'] !== '')
            ->values()
            ->all();
    }

    public static function formatSpecs(?array $specs): string
    {
        return collect($specs ?? [])
            ->map(fn (array $spec) => ($spec['label'] ?? '').': '.($spec['value'] ?? ''))
            ->implode(' '.self::LIST_SEPARATOR.' ');
    }

    /** @return array<int, array{attribute: string, value: string}> */
    public static function parseAttributes(string $value): array
    {
        return collect(self::splitList($value))
            ->map(function (string $pair) {
                [$attribute, $attributeValue] = array_pad(explode('=', $pair, 2), 2, '');

                return ['attribute' => trim($attribute), 'value' => trim($attributeValue)];
            })
            ->filter(fn (array $item) => $item['attribute'] !== '' && $item['value'] !== '')
            ->values()
            ->all();
    }
}
