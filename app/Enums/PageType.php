<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PageType: string implements HasLabel
{
    case Home = 'home';
    case Standard = 'standard';
    case Contact = 'contact';
    case Legal = 'legal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Home => 'Ana sayfa',
            self::Standard => 'Kurumsal sayfa',
            self::Contact => 'İletişim sayfası',
            self::Legal => 'Yasal metin',
        };
    }

    /** Kurumsal Sayfalar modülünde seçilebilen türler (ana sayfa ayrı modülde yönetilir). */
    public static function corporateOptions(): array
    {
        return collect([self::Standard, self::Contact, self::Legal])
            ->mapWithKeys(fn (self $type) => [$type->value => $type->getLabel()])
            ->all();
    }
}
