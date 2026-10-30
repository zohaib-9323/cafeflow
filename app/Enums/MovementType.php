<?php

namespace App\Enums;

enum MovementType: string
{
    case Restock = 'restock';
    case Adjustment = 'adjustment';
    case Waste = 'waste';
    case OrderConsumption = 'order_consumption';
    case OrderReversal = 'order_reversal';

    public function label(): string
    {
        return match ($this) {
            self::Restock => 'Restock',
            self::Adjustment => 'Stock count adjustment',
            self::Waste => 'Waste / spoilage',
            self::OrderConsumption => 'Order consumption',
            self::OrderReversal => 'Order cancelled (returned)',
        };
    }

    /** Types staff can record by hand (the rest are system-generated). */
    public static function manual(): array
    {
        return [self::Restock, self::Adjustment, self::Waste];
    }
}
