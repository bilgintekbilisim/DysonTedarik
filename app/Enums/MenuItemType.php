<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MenuItemType: string implements HasLabel
{
    case Url = 'url';
    case Page = 'page';
    case Category = 'category';
    case Brand = 'brand';
    case Route = 'route';

    public function getLabel(): string
    {
        return match ($this) {
            self::Url => 'Özel / harici bağlantı',
            self::Page => 'Kurumsal sayfa',
            self::Category => 'Ürün kategorisi',
            self::Brand => 'Marka',
            self::Route => 'Sistem sayfası',
        };
    }

    /** Menüye bağlanabilen sabit sistem sayfaları: route adı => etiket. */
    public static function routeOptions(): array
    {
        return [
            'home' => 'Ana sayfa',
            'products.index' => 'Tüm ürünler',
            'brands.index' => 'Markalar',
            'catalogs.index' => 'E-Katalog',
            'contact' => 'İletişim',
            'quote.create' => 'Teklif iste',
        ];
    }
}
