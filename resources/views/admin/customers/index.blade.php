@php use App\Support\Money; @endphp
<x-layouts.admin title="Customers">
    <x-page-header title="Customers" :breadcrumbs="['Customers']" :subtitle="$customers->total().' registered customers'" />
    <form method="GET" class="filter-bar mb-3" role="search">
        <div class="row g-2 align-items-end">
            <div class="col-md-5"><label for="q" class="form-label small mb-1">Search</label><input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Name, email or phone"></div>
            <div class="col-6 col-md-3"><label for="status" class="form-label small mb-1">Account</label>
                <select id="status" name="status" class="form-select"><option value="">All</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Deactivated</option></select></div>
            <div class="col-6 col-md-3"><label for="sort" class="form-label small mb-1">Sort by</label>
                <select id="sort" name="sort" class="form-select">
                    @foreach (['recent' => 'Newest', 'spend' => 'Total spent', 'orders' => 'Orders', 'name' => 'Name'] as $v => $l)<option value="{{ $v }}" @selected(($filters['sort'] ?? 'recent') === $v)>{{ $l }}</option>@endforeach
                </select></div>
            <div class="col-md-1"><button class="btn btn-light w-100">Go</button></div>
        </div>
    </form>
    <div class="card">
        <x-table>
            <x-slot:head><th>Customer</th><th>Phone</th><th class="text-end">Orders</th><th class="text-end">Spent</th><th>Last order</th><th>Status</th></x-slot:head>
            @forelse ($customers as $c)
                <tr>
                    <td data-label="Customer"><a href="{{ route('admin.customers.show', $c) }}" class="fw-semibold">{{ $c->name }}</a><span class="d-block small text-secondary">{{ $c->email }}</span></td>
                    <td data-label="Phone">{{ $c->customer?->phone ?? '—' }}</td>
                    <td data-label="Orders" class="text-end">{{ $c->orders_count }}</td>
                    <td data-label="Spent" class="text-end">{{ Money::format($c->total_spent ?? 0) }}</td>
                    <td data-label="Last order" class="small">{{ $c->last_order_at ? \Illuminate\Support\Carbon::parse($c->last_order_at)->diffForHumans() : 'Never' }}</td>
                    <td data-label="Status"><x-badge :tone="$c->is_active ? 'success' : 'secondary'">{{ $c->is_active ? 'Active' : 'Deactivated' }}</x-badge></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty-state icon="people" title="No customers found" /></td></tr>
            @endforelse
        </x-table>
        @if ($customers->hasPages())<div class="card-footer bg-transparent"><x-pagination :paginator="$customers" /></div>@endif
    </div>
</x-layouts.admin>
