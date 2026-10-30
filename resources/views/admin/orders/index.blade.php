<x-layouts.admin title="Orders">
    <x-page-header title="Orders" :breadcrumbs="['Orders']" :subtitle="auth()->user()->can('manage-orders') ? 'Every order, newest first.' : 'Your orders and the open queue.'">
        <span class="text-secondary small align-self-center" data-result-count>{{ $orders->total() }} orders</span>
    </x-page-header>

    <form method="GET" action="{{ route('admin.orders.index') }}" class="filter-bar mb-3" data-ajax-filter="#ordersTable" role="search">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="q" class="form-label small mb-1">Search</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Order number, name, email or phone">
            </div>
            <div class="col-6 col-md-2">
                <label for="status" class="form-label small mb-1">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $s)<option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="fulfillment" class="form-label small mb-1">Type</label>
                <select id="fulfillment" name="fulfillment" class="form-select">
                    <option value="">Pickup &amp; delivery</option>
                    <option value="pickup" @selected(($filters['fulfillment'] ?? '') === 'pickup')>Pickup</option>
                    <option value="delivery" @selected(($filters['fulfillment'] ?? '') === 'delivery')>Delivery</option>
                </select>
            </div>
            <div class="col-6 col-md-2"><label for="from" class="form-label small mb-1">From</label><input type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control"></div>
            <div class="col-6 col-md-2"><label for="to" class="form-label small mb-1">To</label><input type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control"></div>
        </div>
        @cannot('manage-orders')
            <div class="mt-2 d-flex gap-3 small">
                <label class="form-check-label"><input type="radio" class="form-check-input me-1" name="scope" value="" @checked(empty($filters['scope']))>All visible</label>
                <label class="form-check-label"><input type="radio" class="form-check-input me-1" name="scope" value="mine" @checked(($filters['scope'] ?? '') === 'mine')>Assigned to me</label>
                <label class="form-check-label"><input type="radio" class="form-check-input me-1" name="scope" value="queue" @checked(($filters['scope'] ?? '') === 'queue')>Unassigned</label>
            </div>
        @endcannot
        <noscript><button class="btn btn-primary mt-2">Filter</button></noscript>
    </form>

    <div class="card" id="ordersTable" aria-live="polite">
        @include('admin.orders._table')
    </div>
</x-layouts.admin>
