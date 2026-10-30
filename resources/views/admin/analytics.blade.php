@php
    use App\Support\Money;
    $s = $sales['data']; $p = $products['data']; $c = $customers['data']; $f = $forecast['data'];
    $anyDown = ! $sales['available'] || ! $products['available'] || ! $customers['available'] || ! $forecast['available'];
    $pct = fn ($v) => $v === null ? null : round($v, 1);
@endphp
<x-layouts.admin title="Analytics">
    <x-page-header title="Analytics" :breadcrumbs="['Analytics']" :subtitle="$range->label()">
        @include('admin._range-filter')
        <a href="{{ request()->fullUrlWithQuery(['refresh' => 1]) }}" class="btn btn-sm btn-light align-self-end" title="Recalculate now"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i><span class="visually-hidden">Refresh</span></a>
    </x-page-header>

    @if ($anyDown)
        <x-alert type="warning" :dismissible="false">
            <strong>The analytics service isn't responding.</strong>
            @if ($sales['source'] === 'laravel-fallback') Sales totals below are calculated directly from the order database; product, customer and forecast reports will return once the service is back. @endif
            <span class="d-block small text-secondary mt-1">Service status: {{ $health['available'] ? 'reachable' : 'unreachable' }}. Orders and the shop are not affected.</span>
        </x-alert>
    @endif

    {{-- Sales --}}
    @if ($s)
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3"><x-stat-card label="Revenue" :value="Money::format($s['total_revenue'])" icon="cash-stack" :change="$pct($s['growth']['revenue_growth_pct'] ?? null)" /></div>
            <div class="col-sm-6 col-xl-3"><x-stat-card label="Orders" :value="number_format($s['order_count'])" icon="bag-check" :change="$pct($s['growth']['order_growth_pct'] ?? null)" /></div>
            <div class="col-sm-6 col-xl-3"><x-stat-card label="Average order" :value="Money::format($s['average_order_value'])" icon="receipt" /></div>
            <div class="col-sm-6 col-xl-3"><x-stat-card label="Best day" :value="isset($s['best_day']) ? \Illuminate\Support\Carbon::parse($s['best_day']['date'])->format('D, M j') : '—'" :hint="isset($s['best_day']) ? Money::format($s['best_day']['revenue']) : null" icon="trophy" /></div>
        </div>
        <x-card title="Revenue by day" icon="graph-up" class="mb-3">
            @if (count($s['revenue_by_date']))
                <x-chart label="Daily revenue and orders" :config="[
                    'type' => 'line',
                    'labels' => array_map(fn ($r) => \Illuminate\Support\Carbon::parse($r['date'])->format('M j'), $s['revenue_by_date']),
                    'money' => ['y'],
                    'datasets' => [
                        ['label' => 'Revenue', 'data' => array_column($s['revenue_by_date'], 'revenue')],
                        ['label' => 'Orders', 'data' => array_column($s['revenue_by_date'], 'orders'), 'type' => 'bar', 'yAxisID' => 'y1', 'color' => '#d9c7b0'],
                    ],
                ]" />
            @else
                <x-empty-state icon="graph-up" title="No completed sales in this period" />
            @endif
        </x-card>
    @endif

    <div class="row g-3 mb-3">
        {{-- Products --}}
        <div class="col-xl-7">
            <x-card title="What's selling" icon="cup-hot" flush class="h-100">
                @if (! $p)
                    <div class="card-body"><x-empty-state icon="cloud-slash" title="Product report unavailable" /></div>
                @elseif (! count($p['best_sellers']))
                    <div class="card-body"><x-empty-state icon="cup" title="Nothing sold in this period" /></div>
                @else
                    @php $topShare = max(array_column($p['best_sellers'], 'revenue_share_pct')); @endphp
                    <x-table :stack="false">
                        <x-slot:head><th>Best sellers</th><th class="text-end">Units</th><th class="text-end">Revenue</th><th class="text-end">Share</th></x-slot:head>
                        @foreach ($p['best_sellers'] as $row)
                            <tr><td>{{ $row['product_name'] }}<span class="d-block small text-secondary">{{ $row['category_name'] }}</span></td>
                                <td class="text-end">{{ $row['quantity'] }}</td><td class="text-end">{{ Money::format($row['revenue']) }}</td>
                                <td class="text-end"><div class="d-flex align-items-center gap-2 justify-content-end"><div class="progress flex-grow-1" style="max-width:70px;height:6px" role="presentation"><div class="progress-bar bg-coffee" style="width: {{ $topShare ? round($row['revenue_share_pct'] / $topShare * 100) : 0 }}%"></div></div>{{ round($row['revenue_share_pct']) }}%</div></td></tr>
                        @endforeach
                    </x-table>
                    @if (count($p['lowest_sellers']))
                        <div class="card-body border-top small">
                            <span class="text-secondary">Slow movers:</span>
                            {{ collect($p['lowest_sellers'])->map(fn ($r) => $r['product_name'].' ('.$r['quantity'].')')->implode(', ') }}
                            <span class="d-block text-secondary mt-1">Items with no sales at all in the period don't appear here.</span>
                        </div>
                    @endif
                @endif
            </x-card>
        </div>
        <div class="col-xl-5">
            <x-card title="Revenue by category" icon="pie-chart" class="h-100">
                @if ($p && count($p['categories']))
                    <x-chart label="Revenue share by category" size="sm" :config="['type' => 'doughnut', 'labels' => array_column($p['categories'], 'category_name'), 'datasets' => [['label' => 'Revenue', 'data' => array_column($p['categories'], 'revenue')]]]" />
                    <ul class="list-unstyled small mt-3 mb-0">
                        @foreach ($p['categories'] as $cat)
                            <li class="d-flex justify-content-between py-1 border-bottom"><span>{{ $cat['category_name'] }}</span><span><span class="text-secondary me-2">{{ round($cat['revenue_share_pct']) }}%</span>{{ Money::format($cat['revenue']) }}</span></li>
                        @endforeach
                    </ul>
                @else
                    <x-empty-state icon="pie-chart" title="No category data" />
                @endif
            </x-card>
        </div>
    </div>

    {{-- Customers --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <x-card title="Customers" icon="people" class="h-100">
                @if (! $c)
                    <x-empty-state icon="cloud-slash" title="Customer report unavailable" />
                @elseif (! $c['active_customers'])
                    <x-empty-state icon="people" title="No customer orders in this period" />
                @else
                    <div class="row text-center g-3 mb-3">
                        <div class="col-6 col-md-3"><div class="stat-label">Active</div><div class="fs-4 fw-semibold">{{ $c['active_customers'] }}</div></div>
                        <div class="col-6 col-md-3"><div class="stat-label">First-timers</div><div class="fs-4 fw-semibold">{{ $c['new_customers'] }}</div></div>
                        <div class="col-6 col-md-3"><div class="stat-label">Came back</div><div class="fs-4 fw-semibold">{{ round($c['repeat_rate_pct']) }}%</div></div>
                        <div class="col-6 col-md-3"><div class="stat-label">Spend each</div><div class="fs-4 fw-semibold">{{ Money::format($c['average_spend_per_customer']) }}</div></div>
                    </div>
                    <p class="small text-secondary mb-2">
                        @php $gap = $c['average_days_between_orders'] === null ? null : max(1, (int) round($c['average_days_between_orders'])); @endphp
                        On average a customer ordered {{ $c['average_orders_per_customer'] }} times in this period{{ $gap ? ', about once every '.$gap.' '.($gap === 1 ? 'day' : 'days') : '' }}.
                    </p>
                    <x-chart label="Customers by number of orders" size="sm" :config="['type' => 'bar', 'labels' => array_map(fn ($k) => $k.' '.((string) $k === '1' ? 'order' : 'orders'), array_keys($c['frequency_distribution'])), 'datasets' => [['label' => 'Customers', 'data' => array_values($c['frequency_distribution'])]]]" />
                @endif
            </x-card>
        </div>
        <div class="col-xl-5">
            <x-card title="Top regulars" icon="award" flush class="h-100">
                @if ($c && count($c['top_customers']))
                    <ul class="list-group list-group-flush">
                        @foreach ($c['top_customers'] as $row)
                            <li class="list-group-item d-flex justify-content-between">
                                @can('manage-customers')<a href="{{ route('admin.customers.show', $row['customer_id']) }}">{{ $customerNames[$row['customer_id']] ?? 'Customer #'.$row['customer_id'] }}</a>
                                @else<span>{{ $customerNames[$row['customer_id']] ?? 'Customer #'.$row['customer_id'] }}</span>@endcan
                                <span class="small text-secondary">{{ $row['orders'] }} orders · {{ Money::format($row['spend']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="card-body"><x-empty-state icon="award" title="No regulars yet" /></div>
                @endif
            </x-card>
        </div>
    </div>

    {{-- Forecast --}}
    <x-card title="Next 7 days (estimate)" icon="binoculars" subtitle="Based on the last 12 weeks of completed orders, regardless of the period selected above.">
        @if (! $forecast['available'])
            <x-empty-state icon="hourglass" title="No forecast right now" :message="str_contains((string) ($forecast['error'] ?? ''), '14') ? 'It needs at least 14 days of sales history.' : 'The analytics service could not produce one.'" />
        @else
            @php $m = $f['metadata']; @endphp
            <div class="row g-3">
                <div class="col-lg-8">
                    <x-chart label="Revenue forecast with 80 percent range" :config="[
                        'type' => 'line',
                        'labels' => array_map(fn ($r) => \Illuminate\Support\Carbon::parse($r['date'])->format('D j'), $f['forecast']),
                        'money' => ['y'],
                        'datasets' => [
                            ['label' => 'Expected', 'data' => array_column($f['forecast'], 'revenue')],
                            ['label' => 'Low (80%)', 'data' => array_column($f['forecast'], 'lower_80'), 'color' => '#b08463', 'borderDash' => [4, 4], 'fill' => false],
                            ['label' => 'High (80%)', 'data' => array_column($f['forecast'], 'upper_80'), 'color' => '#b08463', 'borderDash' => [4, 4], 'fill' => false],
                        ],
                    ]" />
                </div>
                <div class="col-lg-4 small">
                    <p class="fs-5 mb-1">{{ Money::format($f['total_forecast_revenue']) }}</p>
                    <p class="text-secondary">expected over the next 7 days. Trend is <strong>{{ $m['trend_direction'] }}</strong>
                        ({{ $m['trend_slope_per_day'] >= 0 ? '+' : '' }}{{ Money::format($m['trend_slope_per_day']) }} per day).</p>
                    <p class="mb-1"><strong>How it's made:</strong> {{ $m['method'] }}. This is a statistical estimate, not machine learning.</p>
                    @if (! empty($m['backtest']['mape_pct']))
                        <p class="mb-1"><strong>Track record:</strong> on the most recent {{ $m['backtest']['holdout_days'] }} days it was off by about {{ round($m['backtest']['mape_pct']) }}% on average.</p>
                    @endif
                    <details class="mt-2"><summary class="text-secondary">Limitations</summary>
                        <ul class="mt-2 ps-3 mb-0">@foreach ($m['limitations'] as $l)<li>{{ $l }}</li>@endforeach</ul>
                    </details>
                </div>
            </div>
        @endif
    </x-card>

    <p class="small text-secondary mt-3 mb-0">
        @if ($sales['available'] && isset($sales['generated_at']))Calculated {{ \Illuminate\Support\Carbon::parse($sales['generated_at'])->diffForHumans() }} by the analytics service. Cancelled and pending orders are excluded.@endif
    </p>
</x-layouts.admin>
