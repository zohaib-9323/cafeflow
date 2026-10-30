<?php

namespace App\Services\Cart;

use App\Enums\FulfillmentType;
use App\Models\Promotion;
use App\Support\Money;

/** A fully server-side priced cart. This is the only place totals come from. */
final readonly class Quote
{
    /**
     * @param  list<QuoteLine>  $lines
     * @param  list<string>  $issues  problems the customer must fix before checking out
     * @param  list<string>  $invalidKeys  cart keys that could not be priced (callers should drop them)
     */
    public function __construct(
        public array $lines,
        public FulfillmentType $fulfillment,
        public int $subtotalCents,
        public int $discountCents,
        public int $taxCents,
        public int $deliveryCents,
        public int $totalCents,
        public ?Promotion $promotion,
        public ?string $promoCode,
        public ?string $promoError,
        public array $issues,
        public array $invalidKeys = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    public function canCheckout(): bool
    {
        return ! $this->isEmpty() && $this->issues === [];
    }

    public function itemCount(): int
    {
        return array_sum(array_map(fn (QuoteLine $l) => $l->quantity, $this->lines));
    }

    public function toArray(): array
    {
        return [
            'lines' => array_map(fn (QuoteLine $l) => $l->toArray(), $this->lines),
            'item_count' => $this->itemCount(),
            'fulfillment_type' => $this->fulfillment->value,
            'subtotal' => Money::fromCents($this->subtotalCents),
            'discount_total' => Money::fromCents($this->discountCents),
            'tax_total' => Money::fromCents($this->taxCents),
            'tax_rate' => (float) config('cafeflow.tax_rate'),
            'delivery_fee' => Money::fromCents($this->deliveryCents),
            'total' => Money::fromCents($this->totalCents),
            'currency' => config('cafeflow.currency'),
            'promotion' => $this->promotion ? ['code' => $this->promotion->code, 'name' => $this->promotion->name] : null,
            'promo_error' => $this->promoError,
            'issues' => $this->issues,
            'can_checkout' => $this->canCheckout(),
        ];
    }
}
