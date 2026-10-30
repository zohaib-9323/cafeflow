<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'short_description' => fake()->sentence(),
            'price' => '4.00',
            'status' => ProductStatus::Active,
            'is_available' => true,
            'is_featured' => false,
            'prep_minutes' => 5,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Draft]);
    }

    /** One unit of the product uses $quantity of the given ingredient. */
    public function usingIngredient(InventoryItem $item, string $quantity): static
    {
        return $this->afterCreating(fn (Product $product) => $product->recipe()->create([
            'inventory_item_id' => $item->id,
            'quantity' => $quantity,
        ]));
    }
}
