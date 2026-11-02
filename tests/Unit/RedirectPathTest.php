<?php

namespace Tests\Unit;

use App\Models\Redirect;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RedirectPathTest extends TestCase
{
    public static function paths(): array
    {
        return [
            'tam adres' => ['https://dysontedarik.com/Eski-Sayfa/?utm=1', '/eski-sayfa'],
            'baştaki eğik çizgi yok' => ['urunler/silikon', '/urunler/silikon'],
            'sondaki eğik çizgi' => ['/kategori/', '/kategori'],
            'kodlanmış karakter' => ['/%C3%BCr%C3%BCn', '/ürün'],
            'kök' => ['/', '/'],
        ];
    }

    #[Test]
    #[DataProvider('paths')]
    public function it_normalizes_paths(string $input, string $expected): void
    {
        $this->assertSame($expected, Redirect::normalizePath($input));
    }
}
