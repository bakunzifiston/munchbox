<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Momo = 'momo';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash on Delivery / Pickup',
            self::Momo => 'Mobile Money',
            self::Card => 'Card',
        };
    }
}
