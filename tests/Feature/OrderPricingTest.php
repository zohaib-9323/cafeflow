<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewOrderReceived;
use App\Notifications\OrderPlaced;
use App\Services\Cart\CartLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsMenu;
use Tests\TestCase;

class OrderPricingTest extends TestCase
{
    use BuildsMenu, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->buildMenu();
    }

    public function test_pickup_order_is_priced_from_the_database(): void
    {
        $customer = User::factory()->create();

        $order = $this->placeOrder($customer, [$this->largeOatLattes()]);

        // 10.80 goods, 8% tax = 0.86, no delivery fee for pickup.
        $this->assertSame('10.80', $order->subtotal);
        $this->assertSame('0.86', $order->tax_total);
        $this->assertSame('0.00', $order->delivery_fee);
        $this->assertSame('11.66', $order->total);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
        $this->assertMatchesRegularExpression('/^CF-\d{6}$/', $order->number);

        $item = $order->items()->with('addons')->sole();
        $this->assertSame('Large', $item->variation_name);
        $this->assertSame('5.40', $item->unit_price);
        $this->assertSame(['Oat milk'], $item->addons->pluck('name')->all());
    }

    public function test_delivery_adds_the_fee_below_the_free_delivery_threshold(): void
    {
        $order = $this->placeOrder(User::factory()->create(), [$this->largeOatLattes()], [
            'fulfillment_type' => 'delivery',
            'delivery_line1' => '18 Orchard Row',
            'delivery_city' => 'Riverton',
            'delivery_postal_code' => '10016',
        ]);

        $this->assertSame('4.50', $order->delivery_fee);
        $this->assertSame('16.16', $order->total); // 10.80 + 0.86 + 4.50
        $this->assertSame('18 Orchard Row', $order->delivery_line1);
    }

    public function test_delivery_below_the_minimum_order_is_refused(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('Delivery needs a minimum order');

        $this->placeOrder(User::factory()->create(), [CartLine::make($this->latte->id, null, [], 1)], [
            'fulfillment_type' => 'delivery',
            'delivery_line1' => '18 Orchard Row',
            'delivery_city' => 'Riverton',
            'delivery_postal_code' => '10016',
        ]);
    }

    public function test_unavailable_and_draft_products_cannot_be_ordered(): void
    {
        $this->latte->update(['is_available' => false]);

        try {
            $this->placeOrder(User::factory()->create(), [$this->largeOatLattes()]);
            $this->fail('Order for an unavailable product was accepted.');
        } catch (BusinessRuleException $e) {
            $this->assertStringContainsString('no longer available', $e->getMessage());
        }
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_an_addon_from_another_product_is_rejected(): void
    {
        $other = Product::factory()->create();
        $foreignAddon = $other->addons()->create(['name' => 'Gold leaf', 'price' => '0.00', 'is_active' => true]);

        $this->expectException(BusinessRuleException::class);
        $this->placeOrder(User::factory()->create(), [CartLine::make($this->latte->id, null, [$foreignAddon->id], 1)]);
    }

    public function test_placing_an_order_notifies_the_customer_and_the_team(): void
    {
        $customer = User::factory()->create();
        $staff = User::factory()->staff()->create();

        $order = $this->placeOrder($customer, [$this->largeOatLattes()]);

        Notification::assertSentTo($customer, OrderPlaced::class, fn (OrderPlaced $n) => $n->order->is($order));
        Notification::assertSentTo($staff, NewOrderReceived::class);
    }
}
