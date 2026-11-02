<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InquiryStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Answered = 'answered';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Yeni',
            self::Reviewing => 'İnceleniyor',
            self::Answered => 'Cevaplandı',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Reviewing => 'info',
            self::Answered => 'success',
        };
    }
}
