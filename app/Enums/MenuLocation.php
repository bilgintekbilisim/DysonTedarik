<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MenuLocation: string implements HasLabel
{
    case Header = 'header';
    case Mobile = 'mobile';
    case FooterCorporate = 'footer_corporate';
    case FooterQuick = 'footer_quick';
    case FooterLegal = 'footer_legal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Header => 'Üst menü',
            self::Mobile => 'Mobil menü',
            self::FooterCorporate => 'Footer — Kurumsal',
            self::FooterQuick => 'Footer — Hızlı bağlantılar',
            self::FooterLegal => 'Footer — Yasal bağlantılar',
        };
    }
}
