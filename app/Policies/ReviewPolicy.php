<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /** Customers may review a product once, after a completed order that included it. */
    public function create(User $user, Product $product): bool
    {
        return $user->hasRole(Role::Customer)
            && ! $product->reviews()->where('user_id', $user->id)->exists()
            && $user->orders()
                ->where('status', OrderStatus::Completed)
                ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
                ->exists();
    }

    public function moderate(User $user, Review $review): bool
    {
        return $user->can('manage-reviews');
    }
}
