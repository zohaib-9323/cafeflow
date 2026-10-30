<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in([...array_column(ProductStatus::cases(), 'value'), 'trashed'])],
            'availability' => ['nullable', Rule::in(['available', 'unavailable'])],
        ]);

        $products = Product::query()
            ->with(['category' => fn ($q) => $q->withTrashed(), 'primaryImage'])
            ->withSum(['orderItems as units_sold'], 'quantity')
            ->when(($filters['status'] ?? null) === 'trashed', fn ($q) => $q->onlyTrashed())
            ->when(($filters['status'] ?? null) && $filters['status'] !== 'trashed', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('category_id', $c))
            ->when($filters['availability'] ?? null, fn ($q, $a) => $q->where('is_available', $a === 'available'))
            ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($q) => $q->where('name', 'like', "%{$t}%")->orWhere('sku', 'like', "%{$t}%")))
            ->orderBy('category_id')->orderBy('sort_order')->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'filters' => $filters,
            'categories' => Category::query()->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Product(['status' => ProductStatus::Active, 'is_available' => true, 'prep_minutes' => 5]));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->products->save(new Product, $request->validated(), $request->file('images', []), $request->user());

        return redirect()->route('admin.products.edit', $product)->with('success', "{$product->name} created.");
    }

    public function edit(Product $product): View
    {
        return $this->form($product->load(['images', 'variations', 'addons', 'recipe']));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->save($product, $request->validated(), $request->file('images', []), $request->user());

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product saved.');
    }

    /** Products with order history are archived (soft-deleted), never hard-deleted. */
    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['status' => ProductStatus::Archived, 'is_featured' => false]);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', "{$product->name} archived. You can restore it from the Archived filter.");
    }

    public function restore(Product $product): RedirectResponse
    {
        $product->restore();
        $product->update(['status' => ProductStatus::Draft]);

        return redirect()->route('admin.products.edit', $product)->with('success', "{$product->name} restored as a draft.");
    }

    private function form(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('sort_order')->get(['id', 'name']),
            'ingredients' => InventoryItem::query()->orderBy('name')->get(['id', 'name', 'unit']),
        ]);
    }
}
