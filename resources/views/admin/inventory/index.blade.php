@php use App\Support\Money; @endphp
<x-layouts.admin title="Inventory">
    <x-page-header title="Inventory" :breadcrumbs="['Inventory']" subtitle="Ingredients and packaging. Stock is taken automatically when orders are confirmed.">
        @can('manage-inventory')
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-light"><i class="bi bi-truck me-1" aria-hidden="true"></i>Suppliers</a>
            <a href="{{ route('admin.inventory.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New item</a>
        @endcan
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-sm-4"><x-stat-card label="Items tracked" :value="$items->total()" icon="box-seam" /></div>
        <div class="col-sm-4"><x-stat-card label="Low stock" :value="$lowCount" icon="exclamation-triangle" :tone="$lowCount ? 'danger' : 'success'" /></div>
        <div class="col-sm-4"><x-stat-card label="Stock value (at cost)" :value="Money::format($stockValue)" icon="cash-stack" /></div>
    </div>

    <form method="GET" class="filter-bar mb-3" role="search">
        <div class="row g-2 align-items-end">
            <div class="col-md-5"><label for="q" class="form-label small mb-1">Search</label><input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Name or SKU"></div>
            <div class="col-6 col-md-3"><label for="stock" class="form-label small mb-1">Stock</label>
                <select id="stock" name="stock" class="form-select"><option value="">All</option><option value="low" @selected(($filters['stock'] ?? '') === 'low')>Low only</option><option value="ok" @selected(($filters['stock'] ?? '') === 'ok')>Healthy</option></select></div>
            <div class="col-6 col-md-3"><label for="supplier" class="form-label small mb-1">Supplier</label>
                <select id="supplier" name="supplier" class="form-select"><option value="">Any</option>@foreach ($suppliers as $s)<option value="{{ $s->id }}" @selected((int) ($filters['supplier'] ?? 0) === $s->id)>{{ $s->name }}</option>@endforeach</select></div>
            <div class="col-md-1"><button class="btn btn-light w-100">Filter</button></div>
        </div>
    </form>

    <div class="card">
        <x-table>
            <x-slot:head><th>Item</th><th>Supplier</th><th class="text-end">In stock</th><th class="text-end">Reorder at</th><th class="text-end">Used in</th><th></th></x-slot:head>
            @forelse ($items as $item)
                <tr @class(['table-danger-subtle' => $item->isLow()])>
                    <td data-label="Item"><a href="{{ route('admin.inventory.show', $item) }}" class="fw-semibold">{{ $item->name }}</a><span class="d-block small text-secondary">{{ $item->sku }}</span></td>
                    <td data-label="Supplier">{{ $item->supplier?->name ?? '—' }}</td>
                    <td data-label="In stock" class="text-end fw-medium">{{ $item->formatQuantity() }} @if ($item->isLow())<x-badge tone="danger">Low</x-badge>@endif</td>
                    <td data-label="Reorder at" class="text-end">{{ $item->formatQuantity($item->low_stock_threshold) }}</td>
                    <td data-label="Used in" class="text-end">{{ $item->recipes_count }} {{ Str::plural('recipe', $item->recipes_count) }}</td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#adjustModal"
                            data-adjust="{{ route('admin.inventory.adjust', $item) }}" data-name="{{ $item->name }}" data-unit="{{ $item->unit->value }}" data-current="{{ $item->formatQuantity() }}">
                            <i class="bi bi-plus-slash-minus me-1" aria-hidden="true"></i>Adjust</button>
                        @can('manage-inventory')<a href="{{ route('admin.inventory.edit', $item) }}" class="btn btn-sm btn-light" aria-label="Edit {{ $item->name }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>@endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty-state icon="box-seam" title="No inventory items match" /></td></tr>
            @endforelse
        </x-table>
        @if ($items->hasPages())<div class="card-footer bg-transparent"><x-pagination :paginator="$items" /></div>@endif
    </div>
    @include('admin.inventory._adjust-modal')
</x-layouts.admin>
