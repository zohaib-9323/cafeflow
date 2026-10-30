<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'sort' => ['nullable', Rule::in(['recent', 'spend', 'orders', 'name'])],
        ]);
        $revenue = OrderStatus::revenueStatuses();

        $customers = User::query()
            ->withRole(Role::Customer)
            ->with('customer:id,user_id,phone')
            ->withCount(['orders' => fn ($q) => $q->whereIn('status', $revenue)])
            ->withSum(['orders as total_spent' => fn ($q) => $q->whereIn('status', $revenue)], 'total')
            ->withMax('orders as last_order_at', 'placed_at')
            ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$t}%")->orWhere('email', 'like', "%{$t}%")
                ->orWhereHas('customer', fn ($q) => $q->where('phone', 'like', "%{$t}%"))))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('is_active', $s === 'active'))
            ->when($filters['sort'] ?? 'recent', fn ($q, $sort) => match ($sort) {
                'spend' => $q->orderByDesc('total_spent'),
                'orders' => $q->orderByDesc('orders_count'),
                'name' => $q->orderBy('name'),
                default => $q->orderByDesc('created_at'),
            })
            ->paginate(20)->withQueryString();

        return view('admin.customers.index', ['customers' => $customers, 'filters' => $filters]);
    }

    public function show(User $user): View
    {
        abort_unless($user->hasRole(Role::Customer), 404);
        $revenue = OrderStatus::revenueStatuses();

        $user->load(['customer.addresses']);
        $stats = $user->orders()->whereIn('status', $revenue)
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total),0) as spent, MIN(placed_at) as first_at, MAX(placed_at) as last_at')
            ->first();

        return view('admin.customers.show', [
            'customer' => $user,
            'stats' => $stats,
            'orders' => $user->orders()->withCount('items')->latest('placed_at')->paginate(10),
            'favourites' => $user->orders()->whereIn('orders.status', $revenue)
                ->join('order_items', 'order_items.order_id', '=', 'orders.id')
                ->groupBy('order_items.product_name')
                ->selectRaw('order_items.product_name, SUM(order_items.quantity) as qty')
                ->orderByDesc('qty')->limit(5)->get(),
            'reviews' => $user->reviews()->with('product:id,name,slug')->latest()->limit(5)->get(),
        ]);
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        $this->authorize('toggleCustomer', $user);
        $user->forceFill(['is_active' => ! $user->is_active])->save();
        if (! $user->is_active) {
            $user->tokens()->delete(); // revoke API access immediately
        }

        return back()->with('success', $user->is_active ? "{$user->name}'s account was reactivated." : "{$user->name}'s account was deactivated.");
    }
}
