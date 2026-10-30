<?php

namespace App\Services;

use App\Enums\PromotionType;
use App\Exceptions\BusinessRuleException;
use App\Models\Promotion;
use App\Models\User;
use App\Support\Money;

class PromotionService
{
    /**
     * Validate a code against a subtotal and return the discount in cents.
     * With $lock (used during order placement inside a transaction), the promotion row
     * is locked so two simultaneous orders cannot both take the last use.
     *
     * @return array{0: Promotion, 1: int}
     *
     * @throws BusinessRuleException
     */
    public function apply(string $code, int $subtotalCents, ?User $user, bool $lock = false): array
    {
        $query = Promotion::query()->where('code', strtoupper(trim($code)));
        $promotion = $lock ? $query->lockForUpdate()->first() : $query->first();

        $fail = fn (string $message) => throw new BusinessRuleException($message, field: 'promo_code');

        if (! $promotion || ! $promotion->is_active) {
            $fail('That promo code is not valid.');
        }
        if ($promotion->starts_at && $promotion->starts_at->isFuture()) {
            $fail('That promo code is not active yet.');
        }
        if ($promotion->expires_at && $promotion->expires_at->isPast()) {
            $fail('That promo code has expired.');
        }
        if ($promotion->min_order_amount !== null && $subtotalCents < Money::toCents($promotion->min_order_amount)) {
            $fail('This code needs a minimum order of '.Money::format($promotion->min_order_amount).'.');
        }
        if ($promotion->usage_limit !== null && $promotion->usages()->count() >= $promotion->usage_limit) {
            $fail('That promo code has reached its usage limit.');
        }
        if ($promotion->per_customer_limit !== null) {
            if (! $user) {
                $fail('Please sign in to use this promo code.');
            }
            if ($promotion->usages()->where('user_id', $user->id)->count() >= $promotion->per_customer_limit) {
                $fail("You've already used this promo code.");
            }
        }

        return [$promotion, $this->discountFor($promotion, $subtotalCents)];
    }

    public function discountFor(Promotion $promotion, int $subtotalCents): int
    {
        $discount = $promotion->type === PromotionType::Percentage
            ? Money::percentOf($subtotalCents, $promotion->value)
            : Money::toCents($promotion->value);

        if ($promotion->max_discount !== null) {
            $discount = min($discount, Money::toCents($promotion->max_discount));
        }

        return max(0, min($discount, $subtotalCents)); // never more than the goods
    }
}
