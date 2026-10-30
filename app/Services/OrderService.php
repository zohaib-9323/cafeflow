<?php

namespace App\Services;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\TransactionStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\ProductRecipe;
use App\Models\PromotionUsage;
use App\Models\User;
use App\Notifications\NewOrderReceived;
use App\Notifications\OrderPlaced;
use App\Notifications\OrderStatusChanged;
use App\Services\Cart\CartLine;
use App\Services\Cart\PricingService;
use App\Services\Cart\QuoteLine;
use App\Services\Payments\PaymentService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly InventoryService $inventory,
        private readonly PaymentService $payments,
    ) {}

    /**
     * Turn a cart into an order. Everything is re-priced from the database inside the
     * transaction, and the promotion row is locked, so a stale or tampered cart can't
     * change what the customer pays.
     *
     * @param  iterable<CartLine>  $lines
     * @param array{fulfillment_type: string, payment_method: string, customer_name: string, customer_phone: string,
     *     promo_code?: ?string, notes?: ?string, delivery_line1?: ?string, delivery_line2?: ?string,
     *     delivery_city?: ?string, delivery_postal_code?: ?string, delivery_instructions?: ?string} $data
     */
    public function place(User $customer, iterable $lines, array $data): Order
    {
        $fulfillment = FulfillmentType::from($data['fulfillment_type']);
        $method = PaymentMethod::from($data['payment_method']);

        $order = DB::transaction(function () use ($customer, $lines, $data, $fulfillment, $method) {
            $quote = $this->pricing->quote($lines, $fulfillment, $data['promo_code'] ?? null, $customer, lockPromotion: true);

            // Issues first: "Latte is no longer available" is more useful than "your cart is empty".
            if ($quote->issues) {
                throw new BusinessRuleException($quote->issues[0], $quote->issues, 'cart');
            }
            if ($quote->isEmpty()) {
                throw new BusinessRuleException('Your cart is empty.', field: 'cart');
            }
            if ($quote->promoError) {
                throw new BusinessRuleException($quote->promoError, field: 'promo_code');
            }

            // Refuse orders we already know we can't make (stock is only taken on confirmation).
            if ($shortages = $this->inventory->shortages($this->inventory->requirementsForQuote($quote->lines))) {
                $products = $this->productsAffectedBy($quote->lines, array_column($shortages, 'inventory_item_id'));
                throw new BusinessRuleException(
                    'Sorry, we have just run out of what we need for: '.implode(', ', $products).'. Please remove it to continue.',
                    field: 'cart',
                );
            }

            $isDelivery = $fulfillment === FulfillmentType::Delivery;
            $order = Order::query()->create([
                'number' => 'TMP-'.Str::random(16), // replaced with CF-… once the id is known
                'user_id' => $customer->id,
                'status' => OrderStatus::Pending,
                'fulfillment_type' => $fulfillment,
                'payment_method' => $method,
                'payment_status' => $method === PaymentMethod::Card ? PaymentStatus::Pending : PaymentStatus::Unpaid,
                'subtotal' => Money::fromCents($quote->subtotalCents),
                'discount_total' => Money::fromCents($quote->discountCents),
                'tax_total' => Money::fromCents($quote->taxCents),
                'delivery_fee' => Money::fromCents($quote->deliveryCents),
                'total' => Money::fromCents($quote->totalCents),
                'currency' => config('cafeflow.currency'),
                'promotion_id' => $quote->promotion?->id,
                'customer_name' => $data['customer_name'],
                'customer_email' => $customer->email,
                'customer_phone' => $data['customer_phone'],
                'delivery_line1' => $isDelivery ? $data['delivery_line1'] : null,
                'delivery_line2' => $isDelivery ? ($data['delivery_line2'] ?? null) : null,
                'delivery_city' => $isDelivery ? $data['delivery_city'] : null,
                'delivery_postal_code' => $isDelivery ? $data['delivery_postal_code'] : null,
                'delivery_instructions' => $isDelivery ? ($data['delivery_instructions'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
            ]);
            $order->forceFill(['number' => self::numberFor($order->id)])->save();

            foreach ($quote->lines as $line) {
                $item = $order->items()->create([
                    'product_id' => $line->product->id,
                    'product_variation_id' => $line->variation?->id,
                    'product_name' => $line->product->name,
                    'variation_name' => $line->variation?->name,
                    'unit_price' => Money::fromCents($line->unitCents),
                    'quantity' => $line->quantity,
                    'line_total' => Money::fromCents($line->lineCents),
                    'recipe_multiplier' => $line->recipeMultiplier(),
                ]);
                foreach ($line->addons as $addon) {
                    $item->addons()->create(['product_addon_id' => $addon->id, 'name' => $addon->name, 'price' => $addon->price]);
                }
            }

            if ($quote->promotion) {
                PromotionUsage::query()->create([
                    'promotion_id' => $quote->promotion->id,
                    'order_id' => $order->id,
                    'user_id' => $customer->id,
                    'discount_amount' => Money::fromCents($quote->discountCents),
                ]);
            }

            if ($method === PaymentMethod::Cash) {
                $order->payments()->create([
                    'provider' => 'cash',
                    'idempotency_key' => "{$order->number}-cash",
                    'amount' => $order->total,
                    'currency' => $order->currency,
                    'status' => TransactionStatus::Pending,
                ]);
            }

            $order->statusHistory()->create(['to_status' => OrderStatus::Pending, 'changed_by' => $customer->id, 'created_at' => now()]);

            $profile = $customer->customer()->firstOrCreate([]);
            if (! $profile->phone) {
                $profile->update(['phone' => $data['customer_phone']]);
            }

            return $order;
        });

        $this->notifyPlaced($order);

        return $order;
    }

    /**
     * Move an order to a new status, enforcing the lifecycle and its side effects:
     * confirming takes stock, cancelling returns it and refunds online payments,
     * completing a cash order records the cash as collected.
     */
    public function transition(Order $order, OrderStatus $to, ?User $actor, ?string $note = null): Order
    {
        $refund = null;

        $order = DB::transaction(function () use ($order, $to, $actor, $note, &$refund) {
            /** @var Order $order */
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $from = $order->status;

            if (! $from->canTransitionTo($to, $order->fulfillment_type)) {
                throw new BusinessRuleException("An order that is {$from->label()} can't be moved to {$to->label()}.", field: 'status');
            }

            switch ($to) {
                case OrderStatus::Confirmed:
                    if ($order->payment_method === PaymentMethod::Card && $order->payment_status !== PaymentStatus::Paid) {
                        throw new BusinessRuleException('This order is waiting for online payment and cannot be confirmed yet.', field: 'status');
                    }
                    // Throws InsufficientStockException (listing the short ingredients) and rolls back.
                    $this->inventory->deductForOrder($order, $actor);
                    $order->confirmed_at = now();
                    break;

                case OrderStatus::Cancelled:
                    $this->inventory->reverseForOrder($order, $actor);
                    $order->cancelled_at = now();
                    $order->cancellation_reason = $note;
                    // A cancelled order shouldn't use up a limited promo code.
                    PromotionUsage::query()->where('order_id', $order->id)->delete();
                    if ($order->payment_status === PaymentStatus::Paid) {
                        $refund = $order->payments()->where('status', TransactionStatus::Succeeded)->first();
                    } elseif ($order->payment_method === PaymentMethod::Cash) {
                        $order->payments()->where('status', TransactionStatus::Pending)->update(['status' => TransactionStatus::Cancelled]);
                    }
                    break;

                case OrderStatus::Completed:
                    $order->completed_at = now();
                    if ($order->payment_method === PaymentMethod::Cash) {
                        $order->payments()->where('status', TransactionStatus::Pending)
                            ->update(['status' => TransactionStatus::Succeeded, 'paid_at' => now()]);
                        $order->payment_status = PaymentStatus::Paid;
                    }
                    break;

                default:
                    break;
            }

            $order->status = $to;
            $order->save();
            $order->statusHistory()->create([
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => $actor?->id,
                'note' => $note,
                'created_at' => now(),
            ]);

            return $order;
        });

        // External refund call happens after the commit: a slow gateway never holds row locks.
        if ($refund) {
            $this->payments->refund($refund);
        }

        $order->user?->notify(new OrderStatusChanged($order));

        return $order->refresh();
    }

    /** Customers may cancel their own order only while nobody has started on it. */
    public function cancelByCustomer(Order $order, User $customer): Order
    {
        if ($order->status !== OrderStatus::Pending) {
            throw new BusinessRuleException('This order is already being prepared and can no longer be cancelled online. Please call us.', field: 'status');
        }

        return $this->transition($order, OrderStatus::Cancelled, $customer, 'Cancelled by customer');
    }

    public function assign(Order $order, ?User $staff, User $actor): Order
    {
        if ($staff && ! $staff->hasRole(Role::Staff, Role::Manager, Role::SuperAdmin)) {
            throw new BusinessRuleException('Orders can only be assigned to team members.', field: 'assigned_to');
        }
        if (! $order->status->isActive()) {
            throw new BusinessRuleException('Finished orders cannot be reassigned.', field: 'assigned_to');
        }
        $order->forceFill(['assigned_to' => $staff?->id])->save();

        return $order;
    }

    public static function numberFor(int $id): string
    {
        return 'CF-'.(100000 + $id);
    }

    /** @param list<QuoteLine> $lines */
    private function productsAffectedBy(array $lines, array $inventoryIds): array
    {
        $recipeProducts = ProductRecipe::query()->whereIn('inventory_item_id', $inventoryIds)->pluck('product_id')->all();
        $names = [];
        foreach ($lines as $line) {
            $addonHit = collect($line->addons)->contains(fn ($a) => in_array($a->inventory_item_id, $inventoryIds, true));
            if (in_array($line->product->id, $recipeProducts, true) || $addonHit) {
                $names[] = $line->product->name;
            }
        }

        return array_values(array_unique($names)) ?: ['some items'];
    }

    private function notifyPlaced(Order $order): void
    {
        try {
            $order->user->notify(new OrderPlaced($order));
            $team = User::query()->withRole(Role::SuperAdmin, Role::Manager, Role::Staff)->where('is_active', true)->get();
            Notification::send($team, new NewOrderReceived($order));
        } catch (\Throwable $e) {
            // A notification problem must never undo a placed order.
            Log::error('Order notifications failed', ['order' => $order->number, 'error' => $e->getMessage()]);
        }
    }
}
