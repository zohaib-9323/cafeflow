<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\Role;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\ProductAddon;
use App\Models\ProductRecipe;
use App\Models\User;
use App\Notifications\LowStockAlert;
use App\Services\Cart\QuoteLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * All stock changes go through this class. Quantities use DECIMAL(12,3) in the
 * database and are handled as integer thousandths here, so repeated deductions
 * never drift.
 */
class InventoryService
{
    private const SCALE = 1000;

    // ---------- requirements ----------

    /**
     * Ingredients needed for a set of quote lines (used before an order exists).
     *
     * @param  list<QuoteLine>  $lines
     * @return array<int, int> inventory_item_id => quantity in thousandths
     */
    public function requirementsForQuote(array $lines): array
    {
        $productIds = array_map(fn (QuoteLine $l) => $l->product->id, $lines);
        $recipes = ProductRecipe::query()->whereIn('product_id', $productIds)->get()->groupBy('product_id');

        $need = [];
        foreach ($lines as $line) {
            $this->accumulate(
                $need,
                $recipes->get($line->product->id, collect()),
                collect($line->addons),
                $line->quantity,
                $line->recipeMultiplier(),
            );
        }

        return $need;
    }

    /** @return array<int, int> */
    public function requirementsForOrder(Order $order): array
    {
        $order->loadMissing('items.addons.addon');
        $recipes = ProductRecipe::query()->whereIn('product_id', $order->items->pluck('product_id'))->get()->groupBy('product_id');

        $need = [];
        foreach ($order->items as $item) {
            $this->accumulate(
                $need,
                $recipes->get($item->product_id, collect()),
                $item->addons->map->addon->filter(),
                $item->quantity,
                (string) $item->recipe_multiplier,
            );
        }

        return $need;
    }

    private function accumulate(array &$need, Collection $recipe, Collection $addons, int $qty, string $multiplier): void
    {
        $mult = (int) round((float) $multiplier * self::SCALE);
        foreach ($recipe as $row) {
            // per-unit qty (thousandths) × multiplier (thousandths) / 1000 × order qty
            $amount = intdiv($this->toUnits($row->quantity) * $mult, self::SCALE) * $qty;
            $need[$row->inventory_item_id] = ($need[$row->inventory_item_id] ?? 0) + $amount;
        }
        foreach ($addons as $addon) {
            /** @var ProductAddon $addon */
            if ($addon->inventory_item_id && $addon->inventory_quantity) {
                $need[$addon->inventory_item_id] = ($need[$addon->inventory_item_id] ?? 0) + $this->toUnits($addon->inventory_quantity) * $qty;
            }
        }
    }

    /**
     * Items that would go below zero if these requirements were taken now.
     *
     * @param  array<int, int>  $need
     * @param  Collection<int, InventoryItem>|null  $items  locked rows to use instead of a fresh read
     */
    public function shortages(array $need, ?Collection $items = null): array
    {
        $items ??= InventoryItem::query()->whereIn('id', array_keys($need))->get()->keyBy('id');
        $shortages = [];
        foreach ($need as $id => $required) {
            $item = $items->get($id);
            if (! $item || $this->mayGoNegative($item)) {
                continue;
            }
            $available = $this->toUnits($item->quantity);
            if ($available < $required) {
                $shortages[] = [
                    'item' => $item->name,
                    'inventory_item_id' => $item->id,
                    'required' => $item->formatQuantity($this->fromUnits($required)),
                    'available' => $item->formatQuantity(),
                ];
            }
        }

        return $shortages;
    }

    // ---------- order lifecycle ----------

    /** Take the ingredients for an order. Must be called inside a transaction. */
    public function deductForOrder(Order $order, ?User $actor = null): void
    {
        if ($order->inventory_deducted_at) {
            return; // never deduct twice
        }

        $need = $this->requirementsForOrder($order);
        if ($need === []) {
            $order->inventory_deducted_at = now();

            return;
        }

        // Lock rows in a stable order (by id) so concurrent confirmations can't deadlock.
        $items = InventoryItem::query()->whereIn('id', array_keys($need))->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        if ($shortages = $this->shortages($need, $items)) {
            throw InsufficientStockException::for($shortages);
        }

        foreach ($need as $id => $amount) {
            $this->move($items[$id], MovementType::OrderConsumption, -$amount, $actor, "Order {$order->number}", $order);
        }
        $order->inventory_deducted_at = now();

        $this->afterCommitCheckLowStock($items);
    }

    /** Put back exactly what an order consumed. Must be called inside a transaction. */
    public function reverseForOrder(Order $order, ?User $actor = null): void
    {
        if (! $order->inventory_deducted_at) {
            return;
        }

        $net = InventoryMovement::query()
            ->where('order_id', $order->id)
            ->whereIn('type', [MovementType::OrderConsumption, MovementType::OrderReversal])
            ->selectRaw('inventory_item_id, SUM(quantity_change) as net')
            ->groupBy('inventory_item_id')
            ->pluck('net', 'inventory_item_id');

        $items = InventoryItem::withTrashed()->whereIn('id', $net->keys())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach ($net as $id => $change) {
            $units = -$this->toUnits($change);
            if ($units > 0) {
                $this->move($items[$id], MovementType::OrderReversal, $units, $actor, "Order {$order->number} cancelled", $order);
            }
        }
        $order->inventory_deducted_at = null;
    }

    // ---------- manual adjustments ----------

    /**
     * Record a restock, waste, or stock-count correction.
     * Restock and waste take a positive amount; an adjustment takes the new counted total.
     */
    public function adjust(InventoryItem $item, MovementType $type, string $quantity, ?string $reason, User $actor): InventoryMovement
    {
        if (! in_array($type, MovementType::manual(), true)) {
            throw new BusinessRuleException('That movement type is recorded automatically.', field: 'type');
        }

        return DB::transaction(function () use ($item, $type, $quantity, $reason, $actor) {
            $item = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);
            $amount = $this->toUnits($quantity);
            $current = $this->toUnits($item->quantity);

            $change = match ($type) {
                MovementType::Restock => $amount,
                MovementType::Waste => -$amount,
                MovementType::Adjustment => $amount - $current,
            };

            if ($change === 0) {
                throw new BusinessRuleException('That would not change the stock level.', field: 'quantity');
            }
            if ($current + $change < 0 && ! $this->mayGoNegative($item)) {
                throw new BusinessRuleException(
                    "Only {$item->formatQuantity()} in stock, so you can't remove that much.",
                    field: 'quantity',
                );
            }

            $movement = $this->move($item, $type, $change, $actor, $reason);

            if (! $item->isLow()) {
                $item->forceFill(['low_stock_notified_at' => null])->save(); // re-arm alerts after a restock
            }
            $this->afterCommitCheckLowStock(collect([$item]));

            return $movement;
        });
    }

    // ---------- low stock ----------

    /** @param Collection<int, InventoryItem> $items */
    public function notifyLowStock(Collection $items): void
    {
        $cooldown = now()->subHours((int) config('cafeflow.low_stock_renotify_hours'));
        $due = InventoryItem::query()
            ->whereIn('id', $items->pluck('id'))
            ->lowStock()
            ->where(fn ($q) => $q->whereNull('low_stock_notified_at')->orWhere('low_stock_notified_at', '<', $cooldown))
            ->get();

        if ($due->isEmpty()) {
            return;
        }

        InventoryItem::query()->whereIn('id', $due->pluck('id'))->update(['low_stock_notified_at' => now()]);

        $recipients = User::query()->withRole(Role::SuperAdmin, Role::Manager)->where('is_active', true)->get();
        Notification::send($recipients, new LowStockAlert($due));

        if ($opsEmail = config('cafeflow.low_stock_email')) {
            Notification::route('mail', $opsEmail)->notify(new LowStockAlert($due, mailOnly: true));
        }
    }

    private function afterCommitCheckLowStock(Collection $items): void
    {
        $ids = $items->pluck('id');
        DB::afterCommit(fn () => $this->notifyLowStock(InventoryItem::query()->whereIn('id', $ids)->get()));
    }

    // ---------- internals ----------

    private function move(InventoryItem $item, MovementType $type, int $change, ?User $actor, ?string $reason, ?Order $order = null): InventoryMovement
    {
        $after = $this->toUnits($item->quantity) + $change;
        $item->forceFill(['quantity' => $this->fromUnits($after)])->save();

        return InventoryMovement::query()->create([
            'inventory_item_id' => $item->id,
            'type' => $type,
            'quantity_change' => $this->fromUnits($change),
            'quantity_after' => $this->fromUnits($after),
            'order_id' => $order?->id,
            'user_id' => $actor?->id,
            'reason' => $reason,
        ]);
    }

    private function mayGoNegative(InventoryItem $item): bool
    {
        return $item->allow_negative || config('cafeflow.allow_negative_stock');
    }

    public function toUnits(string|int|float|null $quantity): int
    {
        return (int) round((float) $quantity * self::SCALE);
    }

    public function fromUnits(int $units): string
    {
        return number_format($units / self::SCALE, 3, '.', '');
    }
}
