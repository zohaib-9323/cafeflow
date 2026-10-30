<?php

namespace App\Enums;

enum PromotionType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return $this === self::Percentage ? 'Percentage' : 'Fixed amount';
    }
}
