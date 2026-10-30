<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    /** Make an image the primary one, or update its alt text. */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'image_id' => ['required', 'integer'],
            'alt' => ['nullable', 'string', 'max:255'],
            'make_primary' => ['sometimes', 'boolean'],
        ]);
        $image = $product->images()->findOrFail($data['image_id']);

        if ($request->boolean('make_primary')) {
            $product->images()->increment('sort_order');
            $image->update(['sort_order' => 0]);
        }
        if ($request->has('alt')) {
            $image->update(['alt' => $data['alt']]);
        }

        return back()->with('success', 'Image updated.');
    }

    public function destroy(Product $product, ProductImage $image): RedirectResponse
    {
        // The file stays in the media library; it can be deleted there once nothing uses it.
        $image->delete();

        return back()->with('success', 'Image removed from this product.');
    }
}
