<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProductStatus;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogController extends ApiController
{
    public function categories(): JsonResponse
    {
        return $this->ok(CategoryResource::collection(
            Category::query()->active()->withCount(['products' => fn ($q) => $q->listed()])->get()
        ));
    }

    public function products(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:120'],
            'available' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', Rule::in(['name', 'price', '-price', 'newest'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = Product::query()->listed()
            ->with(['category', 'primaryImage'])
            ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($q) => $q->where('name', 'like', "%{$t}%")->orWhere('short_description', 'like', "%{$t}%")))
            ->when($filters['category'] ?? null, fn ($q, $slug) => $q->whereHas('category', fn ($q) => $q->where('slug', $slug)))
            ->when(isset($filters['available']), fn ($q) => $q->where('is_available', (bool) $filters['available']))
            ->when(isset($filters['featured']), fn ($q) => $q->where('is_featured', (bool) $filters['featured']))
            ->when(isset($filters['min_price']), fn ($q) => $q->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn ($q) => $q->where('price', '<=', $filters['max_price']))
            ->when($filters['sort'] ?? 'name', fn ($q, $sort) => match ($sort) {
                'price' => $q->orderBy('price'),
                '-price' => $q->orderByDesc('price'),
                'newest' => $q->latest(),
                default => $q->orderBy('name'),
            })
            ->paginate($this->perPage())->withQueryString();

        return $this->ok(ProductResource::collection($products));
    }

    public function product(Product $product): JsonResponse
    {
        abort_unless($product->status === ProductStatus::Active, 404);

        return $this->ok(new ProductResource($product->load(['category', 'images', 'variations', 'addons'])));
    }

    public function store(ProductRequest $request, ProductService $service): JsonResponse
    {
        $product = $service->save(new Product, $request->validated(), [], $request->user());

        return $this->ok(new ProductResource($product->load(['category', 'variations', 'addons'])), 'Product created', 201);
    }

    public function update(ProductRequest $request, Product $product, ProductService $service): JsonResponse
    {
        $service->save($product, $request->validated(), [], $request->user());

        return $this->ok(new ProductResource($product->fresh(['category', 'variations', 'addons'])), 'Product updated');
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->update(['status' => ProductStatus::Archived, 'is_featured' => false]);
        $product->delete();

        return $this->ok(null, 'Product archived');
    }
}
