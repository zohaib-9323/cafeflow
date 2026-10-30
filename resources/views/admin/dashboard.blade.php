@php use App\Support\Money; @endphp
<x-layouts.admin title="Dashboard">
    <x-page-header title="Dashboard" :subtitle="'Showing '.$range->label()">
        @include('admin._range-filter')
    </x-page-header>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><x-stat-card label="Revenue" :value="Money::format($summary['revenue'])" icon="cash-stack" :change="$summary['change']['revenue']" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat-card label="Orders" :value="number_format($summary['orders'])" icon="receipt" :change="$summary['change']['orders']" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat-card label="Avg. order value" :value="Money::format($summary['aov'])" icon="basket" hint="Excludes cancelled orders" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat-card label="Customers" :value="number_format($summary['customers'])" icon="people" :hint="'+'.$summary['new_customers'].' new in period'" /></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <x-card title="Revenue" icon="graph-up" :subtitle="$range->label()">
                <x-chart label="Revenue and orders over time" :config="[
                        'type' => 'line',
                        'labels' => $series['labels'],
                        'money' => ['y'],
                        'datasets' => [
                            ['label' => 'Revenue', 'data' => $series['revenue']],
                            ['label' => 'Orders', 'data' => $series['orders'], 'type' => 'bar', 'yAxisID' => 'y1', 'color' => '#d9c7b0'],
                        ],
                    ]" />
            </x-card>
        </div>
        <div class="col-xl-4">
            <x-card title="Orders by status" icon="pie-chart">
                <div class="d-flex justify-content-around text-center mb-3">
                    <div><div class="h4 mb-0">{{ $summary['pending'] }}</div><div class="small text-secondary">Pending now</div></div>
                    <div><div class="h4 mb-0">{{ $summary['completed'] }}</div><div class="small text-secondary">Completed</div></div>
                    <div><div class="h4 mb-0">{{ $summary['cancelled'] }}</div><div class="small text-secondary">Cancelled</div></div>
                </div>
                @if (array_sum($summary['by_status']))
                    <x-chart label="Order status breakdown" size="sm" :config="[
                        'type' => 'doughnut',
                        'labels' => array_keys(array_filter($summary['by_status'])),
                        'colors' => collect(\App\Enums\OrderStatus::cases())->filter(fn ($s) => $summary['by_status'][$s->label()] > 0)->map->chartColor()->values()->all(),
                        'datasets' => [['label' => 'Orders', 'data' => array_values(array_filter($summary['by_status']))]],
                    ]" />
                @else
                    <x-empty-state icon="pie-chart" title="No orders in this period" />
                @endif
            </x-card>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <x-card title="Recent orders" icon="clock-history" flush>
                <x-slot:actions><a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-light">All orders</a></x-slot:actions>
                <x-table>
                    <x-slot:head><th>Order</th><th>Customer</th><th>Status</th><th class="text-end">Total</th></x-slot:head>
                    @forelse ($recent as $order)
                        <tr>
                            <td data-label="Order"><a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold">{{ $order->number }}</a><span class="d-block small text-secondary">{{ $order->placed_at->diffForHumans() }}</span></td>
                            <td data-label="Customer">{{ $order->customer_name }}</td>
                            <td data-label="Status"><x-status-badge :status="$order->status" /></td>
                            <td data-label="Total" class="text-end">{{ Money::format($order->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty-state title="No orders yet" /></td></tr>
                    @endforelse
                </x-table>
            </x-card>
        </div>
        <div class="col-md-6 col-xl-3">
            <x-card title="Best sellers" icon="trophy" flush>
                <ol class="list-group list-group-flush list-group-numbered">
                    @forelse ($bestSellers as $row)
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto"><div class="fw-medium">{{ $row->product_name }}</div><span class="small text-secondary">{{ Money::format($row->revenue) }}</span></div>
                            <span class="badge text-bg-light border">{{ $row->qty }} sold</span>
                        </li>
                    @empty
                        <li class="list-group-item text-secondary small">No sales in this period.</li>
                    @endforelse
                </ol>
            </x-card>
        </div>
        <div class="col-md-6 col-xl-3">
            <x-card title="Low stock" icon="exclamation-triangle" flush>
                <x-slot:actions><a href="{{ route('admin.inventory.index', ['stock' => 'low']) }}" class="btn btn-sm btn-light">View</a></x-slot:actions>
                <ul class="list-group list-group-flush">
                    @forelse ($lowStock as $item)
                        <li class="list-group-item">
                            <a href="{{ route('admin.inventory.show', $item) }}" class="fw-medium">{{ $item->name }}</a>
                            <div class="small {{ (float) $item->quantity <= 0 ? 'text-danger' : 'text-warning-emphasis' }}">{{ $item->formatQuantity() }} left · reorder at {{ $item->formatQuantity($item->low_stock_threshold) }}</div>
                        </li>
                    @empty
                        <li class="list-group-item small text-success"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>All ingredients above threshold.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>
    </div>
</x-layouts.admin>
