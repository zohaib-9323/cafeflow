<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\DashboardStats;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardStats $stats): View
    {
        $this->authorize('view-dashboard');
        $user = $request->user();

        // Staff get an operational view: their queue, not company revenue.
        if ($user->hasRole(Role::Staff)) {
            return view('admin.dashboard-staff', [
                'mine' => Order::query()->where('assigned_to', $user->id)->whereNotIn('status', ['completed', 'cancelled'])
                    ->withCount('items')->orderBy('placed_at')->get(),
                'queue' => Order::query()->whereNull('assigned_to')->whereNotIn('status', ['completed', 'cancelled'])
                    ->withCount('items')->orderBy('placed_at')->limit(15)->get(),
                'doneToday' => Order::query()->where('assigned_to', $user->id)->where('status', OrderStatus::Completed)
                    ->where('completed_at', '>=', now()->startOfDay())->count(),
                'lowStock' => $stats->lowStock(),
            ]);
        }

        $range = DateRange::fromRequest($request);

        return view('admin.dashboard', [
            'range' => $range,
            'summary' => $stats->summary($range),
            'series' => $stats->revenueSeries($range),
            'bestSellers' => $stats->bestSellers($range),
            'recent' => $stats->recentOrders(),
            'lowStock' => $stats->lowStock(),
        ]);
    }
}
