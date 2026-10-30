<?php

namespace App\Enums;

enum FulfillmentType: string
{
    case Delivery = 'delivery';
    case Pickup = 'pickup';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
