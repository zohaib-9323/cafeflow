<?php

namespace App\Enums;

/** State of a single payment attempt (payments table). */
enum TransactionStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}
