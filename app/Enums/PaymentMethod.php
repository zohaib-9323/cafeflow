<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash on delivery / pickup',
            self::Card => 'Card (online)',
        };
    }
}
