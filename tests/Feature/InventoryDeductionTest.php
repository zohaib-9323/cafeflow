<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\User;
use App\Notifications\LowStockAlert;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsMenu;
use Tests\TestCase;

class InventoryDeductionTest extends TestCase
{
    use BuildsMenu, RefreshDatabase;

    private User $customer;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->buildMenu();
        $this->customer = User::factory()->create();
        $this->staff = User::factory()->staff()->create();
    }

    public function test_placing_an_order_does_not_take_stock(): void
    {
        $this->placeOrder($this->customer, [$this->largeOatLattes()]);

        $this->assertSame('1000.000', $this->beans->fresh()->quantity);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_confirming_deducts_the_recipe_scaled_by_size_and_quantity(): void
    {
        $order = $this->placeOrder($this->customer, [$this->largeOatLattes(2)]);

        $this->confirm($order, $this->staff);

        // Beans: 18 g × 1.5 (Large) × 2 = 54 g. Oat milk add-on: 120 ml × 2 = 240 ml.
        $this->assertSame('946.000', $this->beans->fresh()->quantity);
        $this->assertSame('1760.000', $this->oatMilk->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $this->beans->id,
            'order_id' => $order->id,
            'type' => MovementType::OrderConsumption->value,
            'quantity_change' => '-54.000',
            'user_id' => $this->staff->id,
        ]);
        $this->assertNotNull($order->fresh()->inventory_deducted_at);
    }

    public function test_insufficient_stock_blocks_confirmation_and_changes_nothing(): void
    {
        $order = $this->placeOrder($this->customer, [$this->largeOatLattes(2)]);
        // Someone else used the beans after the order came in: 50 g left, 54 g needed.
        $this->beans->forceFill(['quantity' => '50.000'])->save();

        try {
            $this->confirm($order, $this->staff);
            $this->fail('Confirmation succeeded without enough stock.');
        } catch (InsufficientStockException $e) {
            $this->assertStringContainsString('Espresso beans', $e->getMessage());
        }

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame('50.000', $this->beans->fresh()->quantity);
        $this->assertSame('2000.000', $this->oatMilk->fresh()->quantity, 'partial deduction was not rolled back');
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_an_order_we_already_cannot_make_is_refused_at_checkout(): void
    {
        $this->beans->forceFill(['quantity' => '10.000'])->save();

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('Latte');

        $this->placeOrder($this->customer, [$this->largeOatLattes()]);
    }

    public function test_cancelling_a_confirmed_order_returns_exactly_what_it_used(): void
    {
        $order = $this->confirm($this->placeOrder($this->customer, [$this->largeOatLattes(2)]), $this->staff);

        app(OrderService::class)->transition($order, OrderStatus::Cancelled, $this->staff, 'Customer called to cancel');

        $this->assertSame('1000.000', $this->beans->fresh()->quantity);
        $this->assertSame('2000.000', $this->oatMilk->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', ['order_id' => $order->id, 'type' => MovementType::OrderReversal->value, 'quantity_change' => '54.000']);
    }

    public function test_stock_is_never_taken_twice_for_one_order(): void
    {
        $order = $this->confirm($this->placeOrder($this->customer, [$this->largeOatLattes(1)]), $this->staff);
        $service = app(OrderService::class);
        $service->transition($order, OrderStatus::Preparing, $this->staff);
        $service->transition($order, OrderStatus::ReadyForPickup, $this->staff);
        $service->transition($order, OrderStatus::Completed, $this->staff);

        $this->assertSame('973.000', $this->beans->fresh()->quantity); // 27 g once
        $this->assertSame(1, $order->inventoryMovements()->where('inventory_item_id', $this->beans->id)->count());
    }

    public function test_illegal_status_jumps_are_rejected(): void
    {
        $order = $this->placeOrder($this->customer, [$this->largeOatLattes(1)]);

        $this->expectException(BusinessRuleException::class);
        app(OrderService::class)->transition($order, OrderStatus::Completed, $this->staff);
    }

    public function test_card_orders_cannot_be_confirmed_before_payment(): void
    {
        $order = $this->placeOrder($this->customer, [$this->largeOatLattes(1)], ['payment_method' => 'card']);

        $this->expectExceptionMessage('waiting for online payment');
        $this->confirm($order, $this->staff);
    }

    public function test_crossing_the_low_stock_threshold_alerts_managers_once(): void
    {
        $manager = User::factory()->manager()->create();
        $this->beans->forceFill(['quantity' => '120.000', 'low_stock_threshold' => '100.000'])->save();

        $this->confirm($this->placeOrder($this->customer, [$this->largeOatLattes(1)]), $this->staff); // 93 g left
        $this->confirm($this->placeOrder($this->customer, [$this->largeOatLattes(1)]), $this->staff); // 66 g left

        Notification::assertSentToTimes($manager, LowStockAlert::class, 1);
    }
}
