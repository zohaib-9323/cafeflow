<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use App\Services\MediaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-catalog');
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:170', 'alpha_dash', Rule::unique('products', 'slug')->ignore($product)],
            'sku' => ['nullable', 'string', 'max:40', Rule::unique('products', 'sku')->ignore($product)],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'decimal:0,2', 'min:0', 'max:9999.99'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'is_available' => ['boolean'],
            'is_featured' => ['boolean'],
            'prep_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'calories' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65000'],

            'variations' => ['array', 'max:8'],
            'variations.*.id' => ['nullable', 'integer'],
            'variations.*.name' => ['required', 'string', 'max:80', 'distinct'],
            'variations.*.price_adjustment' => ['required', 'decimal:0,2', 'min:-999.99', 'max:999.99'],
            'variations.*.recipe_multiplier' => ['required', 'numeric', 'min:0.1', 'max:10'],
            'default_variation' => ['nullable', 'integer'],

            'addons' => ['array', 'max:15'],
            'addons.*.id' => ['nullable', 'integer'],
            'addons.*.name' => ['required', 'string', 'max:80', 'distinct'],
            'addons.*.price' => ['required', 'decimal:0,2', 'min:0', 'max:999.99'],
            'addons.*.inventory_item_id' => ['nullable', 'integer', Rule::exists('inventory_items', 'id')->whereNull('deleted_at')],
            'addons.*.inventory_quantity' => ['nullable', 'required_with:addons.*.inventory_item_id', 'numeric', 'min:0.001', 'max:100000'],
            'addons.*.is_active' => ['boolean'],

            'recipe' => ['array', 'max:20'],
            'recipe.*.inventory_item_id' => ['required', 'integer', 'distinct', Rule::exists('inventory_items', 'id')->whereNull('deleted_at')],
            'recipe.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:100000'],

            'images' => ['array', 'max:6'],
            'images.*' => MediaService::rules(),
        ];
    }

    public function messages(): array
    {
        return [
            'variations.*.name.distinct' => 'Each size needs a different name.',
            'recipe.*.inventory_item_id.distinct' => 'Each ingredient can only appear once in the recipe.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name')),
            'is_available' => $this->boolean('is_available'),
            'is_featured' => $this->boolean('is_featured'),
            // Drop the blank template rows the form always submits.
            'variations' => array_values(array_filter((array) $this->input('variations', []), fn ($r) => filled($r['name'] ?? null))),
            'addons' => array_values(array_map(
                fn ($r) => $r + ['is_active' => ! empty($r['is_active'])],
                array_filter((array) $this->input('addons', []), fn ($r) => filled($r['name'] ?? null)),
            )),
            'recipe' => array_values(array_filter((array) $this->input('recipe', []), fn ($r) => filled($r['inventory_item_id'] ?? null))),
        ]);
    }
}
