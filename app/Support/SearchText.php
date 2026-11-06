<?php

namespace App\Support;

/**
 * Arama için metin normalleştirme: Türkçe karakterler ASCII'ye, büyük/küçük harf farkı yok,
 * noktalama boşluğa. "Metal Kesme-Diski 115x2.5" → "metal kesme diski 115x2 5".
 */
class SearchText
{
    public static function normalize(?string $text): string
    {
        $text = strtr((string) $text, [
            'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'I' => 'i', 'İ' => 'i',
            'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u', 'â' => 'a', 'î' => 'i', 'û' => 'u',
        ]);
        $text = strtolower(preg_replace('/[^A-Za-z0-9]+/', ' ', $text));

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /** @return list<string> */
    public static function words(?string $text): array
    {
        $normalized = self::normalize($text);

        return $normalized === '' ? [] : array_values(array_unique(explode(' ', $normalized)));
    }

    /** Boşluksuz biçim: "metalkesme" araması "Metal Kesme Diskleri" ile eşleşsin diye. */
    public static function compact(?string $text): string
    {
        return str_replace(' ', '', self::normalize($text));
    }

    /**
     * Yazım hatasına dayanıklı eşleşme: sorgudaki her kelime metindeki bir kelimeyle
     * aynı başlıyor ya da en fazla 1–2 harf farkla eşleşiyorsa toplam fark puanını döndürür, yoksa null.
     *
     * @param  list<string>  $queryWords
     * @param  list<string>  $textWords
     */
    public static function fuzzyScore(array $queryWords, array $textWords): ?int
    {
        $score = 0;

        foreach ($queryWords as $q) {
            $allowed = strlen($q) <= 3 ? 0 : (strlen($q) <= 5 ? 1 : 2);
            $best = null;

            foreach ($textWords as $w) {
                if (str_starts_with($w, $q)) {
                    $best = 0;
                    break;
                }
                // Uzun kelimelerde yalnızca sorgu uzunluğu kadarlık başı karşılaştır ("antfiriz" ~ "antifriz").
                $distance = min(self::distance($q, $w), self::distance($q, substr($w, 0, strlen($q) + 1)));
                $best = $best === null ? $distance : min($best, $distance);
            }

            if ($best === null || $best > $allowed) {
                return null;
            }
            $score += $best;
        }

        return $score;
    }

    /** Harf ekleme/silme/değiştirme ve yan yana iki harfin yer değiştirmesi ("mteal" → "metal") birer fark sayılır. */
    private static function distance(string $a, string $b): int
    {
        [$la, $lb] = [strlen($a), strlen($b)];
        if (abs($la - $lb) > 3) {
            return max($la, $lb);
        }

        $d = [];
        for ($i = 0; $i <= $la; $i++) {
            $d[$i][0] = $i;
        }
        for ($j = 0; $j <= $lb; $j++) {
            $d[0][$j] = $j;
        }
        for ($i = 1; $i <= $la; $i++) {
            for ($j = 1; $j <= $lb; $j++) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
                $d[$i][$j] = min($d[$i - 1][$j] + 1, $d[$i][$j - 1] + 1, $d[$i - 1][$j - 1] + $cost);
                if ($i > 1 && $j > 1 && $a[$i - 1] === $b[$j - 2] && $a[$i - 2] === $b[$j - 1]) {
                    $d[$i][$j] = min($d[$i][$j], $d[$i - 2][$j - 2] + 1);
                }
            }
        }

        return $d[$la][$lb];
    }
}
