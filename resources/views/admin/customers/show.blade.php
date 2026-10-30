@php use App\Support\Money; use Illuminate\Support\Carbon; @endphp
<x-layouts.admin :title="$customer->name">
    <x-page-header :title="$customer->name" :breadcrumbs="['Customers' => route('admin.customers.index'), $customer->name]" :subtitle="'Customer since '.$customer->created_at->format('M Y')">
        @can('toggleCustomer', $customer)
            <x-confirm :action="route('admin.customers.toggle', $customer)" method="PATCH" :tone="$customer->is_active ? 'danger' : 'primary'"
                :message="$customer->is_active ? 'Deactivate this account? They will be signed out of the API and unable to log in or order.' : 'Reactivate this account?'"
                :confirm-label="$customer->is_active ? 'Deactivate' : 'Reactivate'">
                <button class="btn {{ $customer->is_active ? 'btn-outline-danger' : 'btn-primary' }}">{{ $customer->is_active ? 'Deactivate account' : 'Reactivate account' }}</button>
            </x-confirm>
        @endcan
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-3"><x-stat-card label="Completed/active orders" :value="$stats->orders" icon="bag" /></div>
        <div class="col-sm-6 col-lg-3"><x-stat-card label="Total spent" :value="Money::format($stats->spent)" icon="cash" /></div>
        <div class="col-sm-6 col-lg-3"><x-stat-card label="Average order" :value="$stats->orders ? Money::format($stats->spent / $stats->orders) : '—'" icon="graph-up" /></div>
        <div class="col-sm-6 col-lg-3"><x-stat-card label="Last order" :value="$stats->last_at ? Carbon::parse($stats->last_at)->format('M j, Y') : '—'" icon="calendar" /></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <x-card title="Orders" icon="receipt" flush>
                <x-table>
                    <x-slot:head><th>Order</th><th>Placed</th><th>Status</th><th class="text-end">Total</th></x-slot:head>
                    @forelse ($orders as $o)
                        <tr>
                            <td data-label="Order"><a href="{{ route('admin.orders.show', $o) }}">{{ $o->number }}</a> <span class="small text-secondary">· {{ $o->items_count }} {{ Str::plural('item', $o->items_count) }}</span></td>
                            <td data-label="Placed" class="small">{{ $o->placed_at->format('M j, Y g:i A') }}</td>
                            <td data-label="Status"><x-status-badge :status="$o->status" /></td>
                            <td data-label="Total" class="text-end">{{ Money::format($o->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty-state icon="bag" title="No orders yet" /></td></tr>
                    @endforelse
                </x-table>
                @if ($orders->hasPages())<div class="card-footer bg-transparent"><x-pagination :paginator="$orders" /></div>@endif
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card title="Contact" icon="person-lines-fill" class="mb-3">
                <p class="mb-1"><a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a></p>
                <p class="mb-1">{{ $customer->customer?->phone ?? 'No phone' }}</p>
                <p class="small text-secondary mb-0">Marketing emails: {{ $customer->customer?->marketing_opt_in ? 'opted in' : 'not opted in' }}</p>
                @foreach ($customer->customer?->addresses ?? [] as $a)
                    <p class="small border-top pt-2 mt-2 mb-0"><strong>{{ $a->label }}</strong>@if ($a->is_default) <x-badge tone="light">Default</x-badge>@endif<br>{{ $a->oneLine() }}</p>
                @endforeach
            </x-card>
            <x-card title="Favourites" icon="heart" class="mb-3">
                @forelse ($favourites as $f)
                    <div class="d-flex justify-content-between small py-1"><span>{{ $f->product_name }}</span><span class="text-secondary">× {{ $f->qty }}</span></div>
                @empty
                    <p class="small text-secondary mb-0">No completed orders yet.</p>
                @endforelse
            </x-card>
            <x-card title="Recent reviews" icon="star">
                @forelse ($reviews as $r)
                    <div class="small py-1 border-bottom"><x-stars :rating="$r->rating" /> {{ $r->product?->name }}<div class="text-secondary">{{ Str::limit($r->body, 80) }}</div></div>
                @empty
                    <p class="small text-secondary mb-0">No reviews.</p>
                @endforelse
            </x-card>
        </div>
    </div>
</x-layouts.admin>
