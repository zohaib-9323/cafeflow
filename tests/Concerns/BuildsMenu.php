<?php

namespace Tests\Concerns;

use App\Enums\OrderStatus;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariation;
use App\Models\User;
use App\Services\Cart\CartLine;
use App\Services\OrderService;

/**
 * A small, fully known menu: one latte (18 g beans, Large uses 1.5×) with an
 * oat milk add-on (120 ml). Prices are chosen so totals are easy to check by hand.
 */
trait BuildsMenu
{
    protected InventoryItem $beans;

    protected InventoryItem $oatMilk;

    protected Product $latte;

    protected ProductVariation $large;

    protected ProductAddon $oat;

    protected function buildMenu(string $beans = '1000.000', string $oatMilk = '2000.000'): void
    {
        $this->beans = InventoryItem::factory()->stock($beans)->create(['name' => 'Espresso beans']);
        $this->oatMilk = InventoryItem::factory()->stock($oatMilk)->create(['name' => 'Oat milk', 'unit' => 'ml']);

        $this->latte = Product::factory()->usingIngredient($this->beans, '18')->create(['name' => 'Latte', 'slug' => 'latte', 'price' => '4.00']);
        $this->latte->variations()->create(['name' => 'Regular', 'price_adjustment' => '0', 'recipe_multiplier' => '1', 'is_default' => true]);
        $this->large = $this->latte->variations()->create(['name' => 'Large', 'price_adjustment' => '0.80', 'recipe_multiplier' => '1.5']);
        $this->oat = $this->latte->addons()->create([
            'name' => 'Oat milk', 'price' => '0.60', 'inventory_item_id' => $this->oatMilk->id, 'inventory_quantity' => '120', 'is_active' => true,
        ]);
    }

    /** Two large oat lattes: 2 × (4.00 + 0.80 + 0.60) = 10.80. */
    protected function largeOatLattes(int $quantity = 2): CartLine
    {
        return CartLine::make($this->latte->id, $this->large->id, [$this->oat->id], $quantity);
    }

    protected function placeOrder(User $customer, array $lines, array $overrides = []): Order
    {
        return app(OrderService::class)->place($customer, $lines, $overrides + [
            'fulfillment_type' => 'pickup',
            'payment_method' => 'cash',
            'customer_name' => $customer->name,
            'customer_phone' => '(555) 010-2030',
        ]);
    }

    protected function confirm(Order $order, ?User $actor = null): Order
    {
        return app(OrderService::class)->transition($order, OrderStatus::Confirmed, $actor);
    }
}
