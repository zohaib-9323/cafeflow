<?php

namespace App\Http\Controllers\Site;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CmsBanner;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:120'],
            'q' => ['nullable', 'string', 'max:80'],
        ]);
        $term = trim($validated['q'] ?? '');

        $categories = Category::query()->active()->whereHas('products', fn ($q) => $q->listed())->get();
        $current = isset($validated['category']) ? $categories->firstWhere('slug', $validated['category']) : null;

        $products = Product::query()
            ->listed()
            ->with(['primaryImage', 'category'])
            ->when($current, fn ($q) => $q->where('category_id', $current->id))
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%")))
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        return view('site.menu.index', [
            'categories' => $categories,
            'current' => $current,
            'term' => $term,
            'grouped' => $products->groupBy('category_id'),
            'products' => $products,
            'banner' => CmsBanner::query()->live('menu')->first(),
        ]);
    }

    public function show(Request $request, Product $product): View
    {
        abort_unless($product->status === ProductStatus::Active && $product->category()->where('is_active', true)->exists(), 404);

        $product->load([
            'images', 'category',
            'variations',
            'addons' => fn ($q) => $q->where('is_active', true),
        ]);

        $reviews = $product->reviews()->approved()->with('user:id,name')->latest()->limit(10)->get();
        $rating = $product->reviews()->approved()->selectRaw('AVG(rating) as avg, COUNT(*) as count')->first();

        return view('site.menu.show', [
            'product' => $product,
            'reviews' => $reviews,
            'rating' => $rating,
            'canReview' => $request->user()?->can('create', [Review::class, $product]) ?? false,
            'related' => Product::query()->listed()->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)->with(['primaryImage', 'category'])->inRandomOrder()->limit(4)->get(),
        ]);
    }
}
