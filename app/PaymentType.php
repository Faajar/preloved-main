<?php

namespace App;

enum PaymentType: string
{
    case Listing = 'listing';
    case Boost = 'boost';

    public function label(): string
    {
        return match ($this) {
            self::Listing => 'Listing',
            self::Boost => 'Boost',
        };
    }
}
