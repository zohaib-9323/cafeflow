<?php

namespace Tests\Unit;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Support\Money;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_pickup_and_delivery_lifecycles(): void
    {
        $pickup = FulfillmentType::Pickup;
        $delivery = FulfillmentType::Delivery;

        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Confirmed, $pickup));
        $this->assertTrue(OrderStatus::Preparing->canTransitionTo(OrderStatus::ReadyForPickup, $pickup));
        $this->assertFalse(OrderStatus::Preparing->canTransitionTo(OrderStatus::OutForDelivery, $pickup));
        $this->assertTrue(OrderStatus::Preparing->canTransitionTo(OrderStatus::OutForDelivery, $delivery));
        $this->assertFalse(OrderStatus::Pending->canTransitionTo(OrderStatus::Completed, $pickup));
        $this->assertFalse(OrderStatus::Completed->canTransitionTo(OrderStatus::Cancelled, $pickup));
        $this->assertFalse(OrderStatus::Cancelled->canTransitionTo(OrderStatus::Pending, $pickup));
    }

    public function test_money_math_is_done_in_cents(): void
    {
        $this->assertSame(1080, Money::toCents('10.80'));
        $this->assertSame('0.30', Money::fromCents(Money::toCents('0.10') + Money::toCents('0.20')));
        $this->assertSame('$1,234.50', Money::format('1234.5'));
    }
}
