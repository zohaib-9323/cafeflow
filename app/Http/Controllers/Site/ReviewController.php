<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('create', [Review::class, $product]);

        $review = new Review($request->validated());
        $review->product()->associate($product);
        $review->user()->associate($request->user());
        $review->save();

        return back()->with('success', 'Thanks for your review! It will appear once our team has approved it.');
    }
}
