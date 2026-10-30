<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(private readonly MediaService $media) {}

    /** Create or update a product together with its sizes, add-ons, recipe and new images. */
    public function save(Product $product, array $data, array $images, User $actor): Product
    {
        return DB::transaction(function () use ($product, $data, $images, $actor) {
            $product->fill(Arr::except($data, ['variations', 'addons', 'recipe', 'images', 'default_variation']));
            $product->sort_order ??= 0;
            $product->save();

            $this->syncChildren($product, 'variations', $data['variations'] ?? [], function (array $row, int $i) {
                return [
                    'name' => $row['name'],
                    'price_adjustment' => $row['price_adjustment'],
                    'recipe_multiplier' => $row['recipe_multiplier'],
                    'sort_order' => $i,
                    'is_default' => false,
                ];
            });
            // Exactly one default size when sizes exist.
            $variations = $product->variations()->get();
            if ($variations->isNotEmpty()) {
                $defaultIndex = (int) ($data['default_variation'] ?? 0);
                $default = $variations->get($defaultIndex) ?? $variations->first();
                $product->variations()->update(['is_default' => false]);
                $default->update(['is_default' => true]);
            }

            $this->syncChildren($product, 'addons', $data['addons'] ?? [], fn (array $row, int $i) => [
                'name' => $row['name'],
                'price' => $row['price'],
                'inventory_item_id' => $row['inventory_item_id'] ?? null,
                'inventory_quantity' => filled($row['inventory_item_id'] ?? null) ? ($row['inventory_quantity'] ?? null) : null,
                'is_active' => (bool) ($row['is_active'] ?? true),
                'sort_order' => $i,
            ]);

            // Recipe rows are keyed by ingredient, so a straight replace is the simplest correct sync.
            $product->recipe()->delete();
            foreach ($data['recipe'] ?? [] as $row) {
                $product->recipe()->create(['inventory_item_id' => $row['inventory_item_id'], 'quantity' => $row['quantity']]);
            }

            $nextOrder = (int) $product->images()->max('sort_order') + 1;
            foreach ($images as $file) {
                $media = $this->media->store($file, $actor, $product->name);
                $product->images()->create(['path' => $media->path, 'alt' => $product->name, 'sort_order' => $nextOrder++]);
            }

            return $product;
        });
    }

    /**
     * Update rows that keep their id, create new ones, delete the rest. Existing
     * order_items keep their own name/price snapshot, so removing an option never
     * rewrites history (their foreign keys are set null).
     */
    private function syncChildren(Product $product, string $relation, array $rows, callable $map): void
    {
        $existing = $product->{$relation}()->get()->keyBy('id');
        $keep = [];
        foreach (array_values($rows) as $i => $row) {
            $attributes = $map($row, $i);
            $id = isset($row['id']) ? (int) $row['id'] : null;
            if ($id && $existing->has($id)) {
                $existing[$id]->update($attributes);
                $keep[] = $id;
            } else {
                $keep[] = $product->{$relation}()->create($attributes)->id;
            }
        }
        $product->{$relation}()->whereNotIn('id', $keep ?: [0])->delete();
    }
}
