<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\ReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;

class ReviewController extends ApiController
{
    public function index(Product $product): JsonResponse
    {
        return $this->ok(ReviewResource::collection(
            $product->reviews()->approved()->with('user:id,name')->latest()->paginate($this->perPage())
        ));
    }

    public function store(ReviewRequest $request, Product $product): JsonResponse
    {
        $this->authorize('create', [Review::class, $product]);

        $review = new Review($request->validated());
        $review->product()->associate($product);
        $review->user()->associate($request->user());
        $review->save();

        return $this->ok(new ReviewResource($review), 'Review submitted for moderation', 201);
    }
}
