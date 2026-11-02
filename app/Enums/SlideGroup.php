<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SlideGroup: string implements HasLabel
{
    case Hero = 'hero';
    case Promo = 'promo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hero => 'Ana slider',
            self::Promo => 'Ürün tanıtım bannerı',
        };
    }
}
