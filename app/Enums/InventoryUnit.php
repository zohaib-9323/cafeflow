<?php

namespace App\Enums;

enum InventoryUnit: string
{
    case Gram = 'g';
    case Kilogram = 'kg';
    case Millilitre = 'ml';
    case Litre = 'l';
    case Piece = 'pcs';

    public function label(): string
    {
        return match ($this) {
            self::Gram => 'grams (g)',
            self::Kilogram => 'kilograms (kg)',
            self::Millilitre => 'millilitres (ml)',
            self::Litre => 'litres (l)',
            self::Piece => 'pieces (pcs)',
        };
    }
}
