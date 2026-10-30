<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $term = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;
        $revenue = OrderStatus::revenueStatuses();

        $customers = User::query()->withRole(Role::Customer)->with('customer')
            ->withCount(['orders' => fn ($q) => $q->whereIn('status', $revenue)])
            ->withSum(['orders as total_spent' => fn ($q) => $q->whereIn('status', $revenue)], 'total')
            ->when($term, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->latest()->paginate($this->perPage())->withQueryString();

        return $this->ok(UserResource::collection($customers));
    }

    public function show(User $user): JsonResponse
    {
        abort_unless($user->hasRole(Role::Customer), 404);
        $revenue = OrderStatus::revenueStatuses();
        $user->load('customer')
            ->loadCount(['orders' => fn ($q) => $q->whereIn('status', $revenue)])
            ->loadSum(['orders as total_spent' => fn ($q) => $q->whereIn('status', $revenue)], 'total');

        return $this->ok(new UserResource($user));
    }
}
