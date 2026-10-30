<?php

namespace Database\Factories;

use App\Enums\InventoryUnit;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<InventoryItem> */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => Str::title(fake()->unique()->words(2, true)),
            'sku' => 'INV-'.Str::upper(Str::random(8)),
            'unit' => InventoryUnit::Gram,
            'quantity' => '1000.000',
            'low_stock_threshold' => '100.000',
            'cost_per_unit' => '0.0200',
            'allow_negative' => false,
        ];
    }

    public function stock(string $quantity): static
    {
        return $this->state(fn () => ['quantity' => $quantity]);
    }
}
