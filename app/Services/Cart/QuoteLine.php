<?php

namespace App\Services\Cart;

use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariation;
use App\Support\Money;

final readonly class QuoteLine
{
    /** @param list<ProductAddon> $addons */
    public function __construct(
        public string $key,
        public Product $product,
        public ?ProductVariation $variation,
        public array $addons,
        public int $quantity,
        public int $unitCents,
        public int $lineCents,
    ) {}

    public function recipeMultiplier(): string
    {
        return $this->variation ? (string) $this->variation->recipe_multiplier : '1.000';
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_slug' => $this->product->slug,
            'image_url' => $this->product->imageUrl(),
            'variation' => $this->variation ? ['id' => $this->variation->id, 'name' => $this->variation->name] : null,
            'addons' => array_map(fn (ProductAddon $a) => ['id' => $a->id, 'name' => $a->name, 'price' => $a->price], $this->addons),
            'quantity' => $this->quantity,
            'unit_price' => Money::fromCents($this->unitCents),
            'line_total' => Money::fromCents($this->lineCents),
        ];
    }
}
