<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case ReadyForPickup = 'ready_for_pickup';
    case OutForDelivery = 'out_for_delivery';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Preparing => 'Preparing',
            self::ReadyForPickup => 'Ready for Pickup',
            self::OutForDelivery => 'Out for Delivery',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Bootstrap colour used by status badges. */
    /** Chart colour, matched to the badge tones so the donut and the badges agree. */
    public function chartColor(): string
    {
        return match ($this) {
            self::Pending => '#c9a227',
            self::Confirmed => '#5b7083',
            self::Preparing => '#6f4e37',
            self::ReadyForPickup => '#b08463',
            self::OutForDelivery => '#8a6a4f',
            self::Completed => '#6b8f71',
            self::Cancelled => '#d9c7b0',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::Preparing => 'primary',
            self::ReadyForPickup, self::OutForDelivery => 'accent',
            self::Completed => 'success',
            self::Cancelled => 'secondary',
        };
    }

    /**
     * The order lifecycle. Pickup orders go to "ready for pickup", delivery orders
     * to "out for delivery"; both then complete.
     *
     * @return list<self>
     */
    public function nextStatuses(FulfillmentType $fulfillment): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Preparing, self::Cancelled],
            self::Preparing => [
                $fulfillment === FulfillmentType::Pickup ? self::ReadyForPickup : self::OutForDelivery,
                self::Cancelled,
            ],
            self::ReadyForPickup, self::OutForDelivery => [self::Completed],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to, FulfillmentType $fulfillment): bool
    {
        return in_array($to, $this->nextStatuses($fulfillment), true);
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::Completed, self::Cancelled], true);
    }

    /** Statuses whose revenue counts in reports. */
    public static function revenueStatuses(): array
    {
        return array_map(fn (self $s) => $s->value, array_filter(self::cases(), fn (self $s) => $s !== self::Cancelled));
    }

    /** Customer-facing tracking steps for an order type. */
    public static function trackingSteps(FulfillmentType $fulfillment): array
    {
        return [
            self::Pending,
            self::Confirmed,
            self::Preparing,
            $fulfillment === FulfillmentType::Pickup ? self::ReadyForPickup : self::OutForDelivery,
            self::Completed,
        ];
    }
}
