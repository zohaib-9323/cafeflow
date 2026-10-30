<?php

namespace App\Enums;

/** Payment state as summarised on the order. */
enum PaymentStatus: string
{
    case Unpaid = 'unpaid';     // cash, not yet collected
    case Pending = 'pending';   // card checkout started
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Paid => 'success',
            self::Pending, self::Unpaid => 'warning',
            self::Failed => 'danger',
            self::Refunded => 'secondary',
        };
    }
}
