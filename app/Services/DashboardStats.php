<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\DateRange;

/**
 * Headline numbers for the dashboard, computed in SQL from live orders.
 * (Deeper analysis and the forecast come from the Python service; see AnalyticsService.)
 */
class DashboardStats
{
    public function summary(DateRange $range): array
    {
        $current = $this->totals($range);
        $previous = $this->totals($range->previous());

        $byStatus = Order::query()->placedBetween($range->from, $range->to)
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return [
            'revenue' => $current['revenue'],
            'orders' => $current['orders'],
            'aov' => $current['orders'] ? round($current['revenue'] / $current['orders'], 2) : 0.0,
            'customers' => User::query()->withRole(Role::Customer)->count(),
            'new_customers' => User::query()->withRole(Role::Customer)->whereBetween('created_at', [$range->from, $range->to])->count(),
            'pending' => Order::query()->where('status', OrderStatus::Pending)->count(), // live queue, not range-bound
            'completed' => (int) ($byStatus[OrderStatus::Completed->value] ?? 0),
            'cancelled' => (int) ($byStatus[OrderStatus::Cancelled->value] ?? 0),
            'by_status' => collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $s) => [$s->label() => (int) ($byStatus[$s->value] ?? 0)])->all(),
            'change' => [
                'revenue' => $this->pct($current['revenue'], $previous['revenue']),
                'orders' => $this->pct($current['orders'], $previous['orders']),
            ],
        ];
    }

    /** Revenue and order count per day (or per hour for a single day). */
    public function revenueSeries(DateRange $range): array
    {
        $hourly = $range->days() === 1;
        $bucket = $hourly ? "DATE_FORMAT(placed_at, '%H:00')" : 'DATE(placed_at)';

        $rows = Order::query()->revenue()->placedBetween($range->from, $range->to)
            ->selectRaw("{$bucket} as bucket, SUM(total) as revenue, COUNT(*) as orders")
            ->groupBy('bucket')->get()->keyBy('bucket');

        $labels = $revenue = $orders = [];
        if ($hourly) {
            for ($h = 0; $h < 24; $h++) {
                $key = sprintf('%02d:00', $h);
                $labels[] = $key;
                $revenue[] = round((float) ($rows[$key]->revenue ?? 0), 2);
                $orders[] = (int) ($rows[$key]->orders ?? 0);
            }
        } else {
            for ($d = $range->from; $d->lte($range->to); $d = $d->addDay()) {
                $key = $d->toDateString();
                $labels[] = $d->format('M j');
                $revenue[] = round((float) ($rows[$key]->revenue ?? 0), 2);
                $orders[] = (int) ($rows[$key]->orders ?? 0);
            }
        }

        return compact('labels', 'revenue', 'orders');
    }

    public function bestSellers(DateRange $range, int $limit = 5)
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', OrderStatus::revenueStatuses())
            ->whereBetween('orders.placed_at', [$range->from, $range->to])
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->selectRaw('order_items.product_id, order_items.product_name, SUM(order_items.quantity) as qty, SUM(order_items.line_total) as revenue')
            ->orderByDesc('qty')
            ->limit($limit)
            ->get();
    }

    public function recentOrders(int $limit = 8)
    {
        return Order::query()->latest('placed_at')->limit($limit)
            ->get(['id', 'number', 'customer_name', 'status', 'total', 'placed_at', 'fulfillment_type']);
    }

    public function lowStock(int $limit = 6)
    {
        return InventoryItem::query()->lowStock()->orderByRaw('quantity / NULLIF(low_stock_threshold, 0)')->limit($limit)->get();
    }

    private function totals(DateRange $range): array
    {
        $row = Order::query()->revenue()->placedBetween($range->from, $range->to)
            ->selectRaw('COALESCE(SUM(total), 0) as revenue, COUNT(*) as orders')->first();

        return ['revenue' => round((float) $row->revenue, 2), 'orders' => (int) $row->orders];
    }

    private function pct(float|int $current, float|int $previous): ?float
    {
        return $previous ? round(($current - $previous) / $previous * 100, 1) : null;
    }
}
