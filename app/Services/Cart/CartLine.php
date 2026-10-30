<?php

namespace App\Services\Cart;

/** A cart line as submitted: identifiers and quantity only. Prices are never taken from input. */
final readonly class CartLine
{
    /** @param list<int> $addonIds */
    public function __construct(
        public int $productId,
        public ?int $variationId,
        public array $addonIds,
        public int $quantity,
    ) {}

    public static function make(int $productId, ?int $variationId, array $addonIds, int $quantity): self
    {
        $addonIds = array_values(array_unique(array_map('intval', $addonIds)));
        sort($addonIds);

        return new self($productId, $variationId ?: null, $addonIds, $quantity);
    }

    public static function fromArray(array $data): self
    {
        return self::make(
            (int) ($data['product_id'] ?? 0),
            isset($data['variation_id']) ? (int) $data['variation_id'] : null,
            (array) ($data['addon_ids'] ?? []),
            (int) ($data['quantity'] ?? 1),
        );
    }

    /** Identical product + options merge into one line. */
    public function key(): string
    {
        return $this->productId.':'.($this->variationId ?? 0).':'.implode(',', $this->addonIds);
    }

    public function withQuantity(int $quantity): self
    {
        return new self($this->productId, $this->variationId, $this->addonIds, $quantity);
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'variation_id' => $this->variationId,
            'addon_ids' => $this->addonIds,
            'quantity' => $this->quantity,
        ];
    }
}
