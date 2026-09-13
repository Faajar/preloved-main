<?php

namespace App;

enum ProductStatus: string
{
    case Available = 'available';
    case Sold = 'sold';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Sold => 'Terjual',
            self::Hidden => 'Disembunyikan',
        };
    }
}
