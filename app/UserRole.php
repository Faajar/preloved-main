<?php

namespace App;

enum UserRole: string
{
    case Buyer = 'buyer';
    case Seller = 'seller';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Buyer => 'Buyer',
            self::Seller => 'Seller',
            self::Admin => 'Admin',
        };
    }

    public function canSell(): bool
    {
        return $this === self::Seller;
    }
}
