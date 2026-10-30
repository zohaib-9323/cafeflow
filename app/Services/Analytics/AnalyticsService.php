<?php

namespace App\Services\Analytics;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\DashboardStats;
use App\Support\DateRange;
use Illuminate\Support\Facades\Cache;

/**
 * Builds payloads from Laravel's database (the source of truth), sends them to the
 * Python service and caches the results. If the service is down, every report comes
 * back with `available: false` plus a basic Laravel-computed summary where possible,
 * so the dashboard degrades instead of breaking.
 */
class AnalyticsService
{
    public function __construct(private readonly AnalyticsClient $client, private readonly DashboardStats $stats) {}

    public function sales(DateRange $range): array
    {
        return $this->cached('sales', $range, fn () => $this->client->post('sales', [
            'start_date' => $range->from->toDateString(),
            'end_date' => $range->to->toDateString(),
            'orders' => $this->orderRows($range),
            'previous_orders' => $this->orderRows($range->previous()),
        ]), fn () => $this->salesFallback($range));
    }

    public function products(DateRange $range, int $limit = 5): array
    {
        return $this->cached("products.{$limit}", $range, function () use ($range, $limit) {
            // Pre-aggregated per product in SQL so the payload stays small.
            $items = OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->whereIn('orders.status', OrderStatus::revenueStatuses())
                ->whereBetween('orders.placed_at', [$range->from, $range->to])
                ->groupBy('order_items.product_id', 'products.name', 'categories.name')
                ->selectRaw('order_items.product_id, products.name as product_name, categories.name as category_name,
                             SUM(order_items.quantity) as quantity, SUM(order_items.line_total) as revenue')
                ->get()
                ->map(fn ($r) => [
                    'product_id' => (int) $r->product_id,
                    'product_name' => $r->product_name,
                    'category_name' => $r->category_name,
                    'quantity' => (int) $r->quantity,
                    'revenue' => number_format((float) $r->revenue, 2, '.', ''),
                ])->all();

            return $this->client->post('products', ['items' => $items, 'limit' => $limit]);
        });
    }

    public function customers(DateRange $range): array
    {
        return $this->cached('customers', $range, function () use ($range) {
            $orders = Order::query()->revenue()->placedBetween($range->from, $range->to)
                ->get(['id', 'user_id', 'placed_at', 'total']);

            $firstOrders = Order::query()->revenue()
                ->whereIn('user_id', $orders->pluck('user_id')->unique())
                ->groupBy('user_id')
                ->selectRaw('user_id, MIN(placed_at) as first_at')
                ->pluck('first_at', 'user_id')
                ->map(fn ($d) => substr((string) $d, 0, 10));

            return $this->client->post('customers', [
                'start_date' => $range->from->toDateString(),
                'end_date' => $range->to->toDateString(),
                'orders' => $orders->map(fn (Order $o) => [
                    'order_id' => $o->id,
                    'customer_id' => $o->user_id,
                    'placed_at' => $o->placed_at->toIso8601String(),
                    'total' => (string) $o->total,
                ])->all(),
                'first_order_dates' => $firstOrders->all(),
            ]);
        });
    }

    /**
     * 7-day forecast from 12 weeks of daily revenue ending yesterday. Today is left
     * out because a half-finished day would read as a sudden drop.
     */
    public function forecast(int $horizon = 7): array
    {
        $to = now()->subDay()->endOfDay();
        $from = now()->subDays(84)->startOfDay();
        $range = new DateRange($from->toImmutable(), $to->toImmutable(), 'forecast');

        return $this->cached("forecast.{$horizon}", $range, function () use ($from, $to, $horizon) {
            $daily = Order::query()->revenue()->whereBetween('placed_at', [$from, $to])
                ->selectRaw('DATE(placed_at) as d, SUM(total) as revenue')
                ->groupBy('d')->pluck('revenue', 'd');

            // Send every day in the window (zeros included) so the series is explicit.
            $history = [];
            for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
                $history[] = ['date' => $day->toDateString(), 'revenue' => number_format((float) ($daily[$day->toDateString()] ?? 0), 2, '.', '')];
            }

            return $this->client->post('forecast', ['history' => $history, 'horizon_days' => $horizon]);
        });
    }

    public function health(): array
    {
        try {
            return ['available' => true] + $this->client->health();
        } catch (AnalyticsUnavailable $e) {
            return ['available' => false, 'error' => $e->getMessage()];
        }
    }

    public function forget(): void
    {
        Cache::increment('analytics.version');
    }

    private function cached(string $name, DateRange $range, callable $fetch, ?callable $fallback = null): array
    {
        $version = Cache::get('analytics.version', 1);
        $key = "analytics.v{$version}.{$name}.{$range->from->toDateString()}.{$range->to->toDateString()}";

        if ($hit = Cache::get($key)) {
            return $hit;
        }

        try {
            $result = ['available' => true, 'source' => 'python', 'data' => $fetch(), 'generated_at' => now()->toIso8601String()];
            Cache::put($key, $result, config('cafeflow.analytics.cache_seconds'));

            return $result;
        } catch (AnalyticsUnavailable $e) {
            // Not cached, so the next request tries the service again.
            return [
                'available' => false,
                'source' => $fallback ? 'laravel-fallback' : null,
                'data' => $fallback ? $fallback() : null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function orderRows(DateRange $range): array
    {
        return Order::query()->revenue()->placedBetween($range->from, $range->to)
            ->get(['id', 'user_id', 'placed_at', 'total'])
            ->map(fn (Order $o) => [
                'order_id' => $o->id,
                'customer_id' => $o->user_id,
                'placed_at' => $o->placed_at->toIso8601String(),
                'total' => (string) $o->total,
            ])->all();
    }

    /** Minimal sales figures computed locally when Python is unavailable. */
    private function salesFallback(DateRange $range): array
    {
        $summary = $this->stats->summary($range);
        $series = $this->stats->revenueSeries($range);

        return [
            'total_revenue' => $summary['revenue'],
            'order_count' => $summary['orders'],
            'average_order_value' => $summary['aov'],
            'growth' => ['revenue_growth_pct' => $summary['change']['revenue'], 'order_growth_pct' => $summary['change']['orders']],
            'revenue_by_date' => collect($series['labels'])->map(fn ($l, $i) => ['date' => $l, 'revenue' => $series['revenue'][$i], 'orders' => $series['orders'][$i]])->all(),
        ];
    }
}
