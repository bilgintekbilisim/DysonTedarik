<?php

namespace App\Support;

/**
 * Ürün ölçü tablosu (ebat / artikel / ambalaj) ile paneldeki metin alanı arasında dönüşüm.
 * Panelde Excel'den kopyalanan satırlar yapıştırılabilir: ilk satır başlık, sütunlar sekme veya "|" ile ayrılır.
 */
class VariantTable
{
    public const MAX_ROWS = 500;

    /** @return array{headers: array<int, string>, rows: array<int, array<int, string>>}|null */
    public static function fromText(?string $text): ?array
    {
        $lines = array_values(array_filter(
            preg_split('/\r\n|\r|\n/', (string) $text),
            fn ($line) => trim($line) !== '',
        ));

        if (count($lines) < 2) {
            return null;
        }

        $split = fn (string $line) => array_map(
            fn ($cell) => mb_substr(trim(strip_tags($cell)), 0, 120),
            preg_split(str_contains($line, "\t") ? '/\t/' : '/\s*\|\s*/', trim($line, ' |')),
        );

        $headers = $split(array_shift($lines));
        $rows = array_map(fn ($line) => array_pad(array_slice($split($line), 0, count($headers)), count($headers), ''), array_slice($lines, 0, self::MAX_ROWS));

        return ['headers' => $headers, 'rows' => $rows];
    }

    public static function toText(?array $table): string
    {
        if (empty($table['headers'])) {
            return '';
        }

        return collect([$table['headers'], ...($table['rows'] ?? [])])
            ->map(fn (array $cells) => implode(' | ', $cells))
            ->implode("\n");
    }
}
