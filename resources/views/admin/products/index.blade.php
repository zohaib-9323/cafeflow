@php use App\Support\Money; @endphp
<x-layouts.admin title="Products">
    <x-page-header title="Products" :breadcrumbs="['Products']" :subtitle="$products->total().' products'">
        @can('manage-catalog')<a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New product</a>@endcan
    </x-page-header>

    <form method="GET" class="filter-bar mb-3" role="search">
        <div class="row g-2 align-items-end">
            <div class="col-md-4"><label for="q" class="form-label small mb-1">Search</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Name or SKU"></div>
            <div class="col-6 col-md-3"><label for="category" class="form-label small mb-1">Category</label>
                <select id="category" name="category" class="form-select"><option value="">All categories</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected((int) ($filters['category'] ?? 0) === $c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><label for="status" class="form-label small mb-1">Status</label>
                <select id="status" name="status" class="form-select"><option value="">Any</option>
                    @foreach (\App\Enums\ProductStatus::cases() as $s)<option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>@endforeach
                    <option value="trashed" @selected(($filters['status'] ?? '') === 'trashed')>Deleted (restorable)</option></select></div>
            <div class="col-6 col-md-2"><label for="availability" class="form-label small mb-1">Today</label>
                <select id="availability" name="availability" class="form-select"><option value="">Any</option>
                    <option value="available" @selected(($filters['availability'] ?? '') === 'available')>Available</option>
                    <option value="unavailable" @selected(($filters['availability'] ?? '') === 'unavailable')>Sold out</option></select></div>
            <div class="col-6 col-md-1"><button class="btn btn-light w-100">Filter</button></div>
        </div>
    </form>

    <div class="card">
        <x-table>
            <x-slot:head><th>Product</th><th>Category</th><th>Status</th><th class="text-end">Price</th><th class="text-end">Sold</th><th></th></x-slot:head>
            @forelse ($products as $product)
                <tr>
                    <td data-label="Product">
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $product->imageUrl() }}" alt="" width="44" height="44" class="rounded object-fit-cover" loading="lazy">
                            <div><span class="fw-semibold">{{ $product->name }}</span>
                                @if ($product->is_featured)<i class="bi bi-star-fill text-warning ms-1" title="Featured" aria-label="Featured"></i>@endif
                                <span class="d-block small text-secondary">{{ $product->sku ?: '—' }}</span></div>
                        </div>
                    </td>
                    <td data-label="Category">{{ $product->category?->name }}</td>
                    <td data-label="Status">
                        @if ($product->trashed())<x-badge tone="dark">Deleted</x-badge>
                        @else<x-badge :tone="$product->status->tone()">{{ $product->status->label() }}</x-badge>
                            @unless ($product->is_available)<x-badge tone="danger">Sold out</x-badge>@endunless
                        @endif
                    </td>
                    <td data-label="Price" class="text-end">{{ Money::format($product->price) }}</td>
                    <td data-label="Sold" class="text-end">{{ (int) $product->units_sold }}</td>
                    <td class="text-end text-nowrap">
                        @can('manage-catalog')
                            @if ($product->trashed())
                                <form method="POST" action="{{ route('admin.products.restore', $product) }}" class="d-inline">@csrf<button class="btn btn-sm btn-light">Restore</button></form>
                            @else
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-light">Edit</a>
                                <x-confirm :action="route('admin.products.destroy', $product)" method="DELETE" class="d-inline"
                                    :message="'Archive '.$product->name.'? It disappears from the menu but order history is kept.'" confirm-label="Archive">
                                    <button class="btn btn-sm btn-outline-danger" aria-label="Archive {{ $product->name }}"><i class="bi bi-archive" aria-hidden="true"></i></button>
                                </x-confirm>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty-state icon="cup-hot" title="No products found" /></td></tr>
            @endforelse
        </x-table>
        @if ($products->hasPages())<div class="card-footer bg-transparent"><x-pagination :paginator="$products" /></div>@endif
    </div>
</x-layouts.admin>
