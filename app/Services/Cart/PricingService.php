<?php

namespace App\Services\Cart;

use App\Enums\FulfillmentType;
use App\Exceptions\BusinessRuleException;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\User;
use App\Services\PromotionService;
use App\Support\Money;

/**
 * Prices a cart from the database. The cart page, the JSON API and order placement
 * all go through here, so the customer is always charged what they were shown.
 */
class PricingService
{
    public function __construct(private readonly PromotionService $promotions) {}

    /** @param iterable<CartLine> $cartLines */
    public function quote(
        iterable $cartLines,
        FulfillmentType $fulfillment,
        ?string $promoCode = null,
        ?User $user = null,
        bool $lockPromotion = false,
    ): Quote {
        $cartLines = collect($cartLines)->values();
        $products = Product::query()
            ->with(['variations', 'addons' => fn ($q) => $q->where('is_active', true), 'primaryImage', 'category'])
            ->whereIn('id', $cartLines->pluck('productId')->unique())
            ->get()
            ->keyBy('id');

        $lines = [];
        $issues = [];
        $invalidKeys = [];
        $maxQty = (int) config('cafeflow.max_item_quantity');

        foreach ($cartLines as $cartLine) {
            /** @var CartLine $cartLine */
            $product = $products->get($cartLine->productId);

            if (! $product || ! $product->isOrderable() || ! $product->category?->is_active) {
                $issues[] = ($product?->name ?? 'An item').' is no longer available and was left out.';
                $invalidKeys[] = $cartLine->key();

                continue;
            }

            $variation = null;
            if ($product->variations->isNotEmpty()) {
                $variation = $cartLine->variationId
                    ? $product->variations->firstWhere('id', $cartLine->variationId)
                    : ($product->variations->firstWhere('is_default', true) ?? $product->variations->first());
                if (! $variation) {
                    $issues[] = "The selected size for {$product->name} is no longer offered.";
                    $invalidKeys[] = $cartLine->key();

                    continue;
                }
            } elseif ($cartLine->variationId) {
                $issues[] = "The selected size for {$product->name} is no longer offered.";
                $invalidKeys[] = $cartLine->key();

                continue;
            }

            $addons = $product->addons->whereIn('id', $cartLine->addonIds)->values();
            if ($addons->count() !== count($cartLine->addonIds)) {
                $issues[] = "Some extras for {$product->name} are no longer offered.";
                $invalidKeys[] = $cartLine->key();

                continue;
            }

            $quantity = max(1, min($cartLine->quantity, $maxQty));
            $unit = Money::toCents($product->price)
                + ($variation ? Money::toCents($variation->price_adjustment) : 0)
                + $addons->sum(fn (ProductAddon $a) => Money::toCents($a->price));
            $unit = max(0, $unit);

            $lines[] = new QuoteLine($cartLine->key(), $product, $variation, $addons->all(), $quantity, $unit, $unit * $quantity);
        }

        $subtotal = array_sum(array_map(fn (QuoteLine $l) => $l->lineCents, $lines));

        $promotion = null;
        $discount = 0;
        $promoError = null;
        if ($promoCode && $lines) {
            try {
                [$promotion, $discount] = $this->promotions->apply($promoCode, $subtotal, $user, $lockPromotion);
            } catch (BusinessRuleException $e) {
                $promoError = $e->getMessage();
            }
        }

        $afterDiscount = $subtotal - $discount;
        $delivery = 0;
        if ($fulfillment === FulfillmentType::Delivery && $lines) {
            $minimum = Money::toCents(config('cafeflow.min_delivery_order'));
            if ($afterDiscount < $minimum) {
                $issues[] = 'Delivery needs a minimum order of '.Money::format($minimum).'. Choose pickup or add more items.';
            }
            $delivery = $afterDiscount >= Money::toCents(config('cafeflow.free_delivery_over'))
                ? 0
                : Money::toCents(config('cafeflow.delivery_fee'));
        }

        // Tax is charged on goods after discount; the delivery fee is not taxed.
        $tax = (int) round($afterDiscount * (float) config('cafeflow.tax_rate'), 0, PHP_ROUND_HALF_UP);

        return new Quote(
            lines: $lines,
            fulfillment: $fulfillment,
            subtotalCents: $subtotal,
            discountCents: $discount,
            taxCents: $tax,
            deliveryCents: $delivery,
            totalCents: $afterDiscount + $tax + $delivery,
            promotion: $promotion,
            promoCode: $promoCode,
            promoError: $promoError,
            issues: $issues,
            invalidKeys: $invalidKeys,
        );
    }
}
