@php use App\Support\Money; @endphp
<x-layouts.admin :title="$item->name">
    <x-page-header :title="$item->name" :breadcrumbs="['Inventory' => route('admin.inventory.index'), $item->name]" :subtitle="$item->sku.($item->supplier ? ' · '.$item->supplier->name : '')">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustModal"
            data-adjust="{{ route('admin.inventory.adjust', $item) }}" data-name="{{ $item->name }}" data-unit="{{ $item->unit->value }}" data-current="{{ $item->formatQuantity() }}">
            <i class="bi bi-plus-slash-minus me-1" aria-hidden="true"></i>Record change</button>
        @can('manage-inventory')<a href="{{ route('admin.inventory.edit', $item) }}" class="btn btn-light">Edit</a>@endcan
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-3"><x-stat-card label="In stock" :value="$item->formatQuantity()" icon="box-seam" :tone="$item->isLow() ? 'danger' : 'success'" /></div>
        <div class="col-sm-6 col-lg-3"><x-stat-card label="Reorder at" :value="$item->formatQuantity($item->low_stock_threshold)" icon="bell" /></div>
        <div class="col-sm-6 col-lg-3"><x-stat-card label="Cost per unit" :value="$item->cost_per_unit ? '$'.rtrim(rtrim($item->cost_per_unit, '0'), '.').' / '.$item->unit->value : '—'" icon="tag" /></div>
        <div class="col-sm-6 col-lg-3"><x-stat-card label="Negative stock" :value="$item->allow_negative ? 'Allowed' : 'Blocked'" icon="shield-check" /></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <x-card title="Movement history" icon="clock-history" flush>
                <x-table>
                    <x-slot:head><th>When</th><th>Type</th><th class="text-end">Change</th><th class="text-end">After</th><th>By / reference</th></x-slot:head>
                    @forelse ($movements as $m)
                        <tr>
                            <td data-label="When" class="small">{{ $m->created_at->format('M j, g:i A') }}</td>
                            <td data-label="Type">{{ $m->type->label() }}</td>
                            <td data-label="Change" class="text-end {{ (float) $m->quantity_change < 0 ? 'text-danger' : 'text-success' }}">{{ (float) $m->quantity_change > 0 ? '+' : '' }}{{ $item->formatQuantity($m->quantity_change) }}</td>
                            <td data-label="After" class="text-end">{{ $item->formatQuantity($m->quantity_after) }}</td>
                            <td data-label="By" class="small">{{ $m->user?->name ?? 'System' }}
                                @if ($m->order)· <a href="{{ route('admin.orders.show', $m->order) }}">{{ $m->order->number }}</a>@endif
                                @if ($m->reason && ! $m->order)<span class="d-block text-secondary">{{ $m->reason }}</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="clock" title="No movements yet" /></td></tr>
                    @endforelse
                </x-table>
                @if ($movements->hasPages())<div class="card-footer bg-transparent"><x-pagination :paginator="$movements" /></div>@endif
            </x-card>
        </div>
        <div class="col-lg-4">
            <x-card title="Used in recipes" icon="journal-text">
                @forelse ($usedIn as $r)
                    <div class="d-flex justify-content-between small py-1 border-bottom">
                        @can('manage-catalog')<a href="{{ route('admin.products.edit', $r->product) }}">{{ $r->product->name }}</a>@else<span>{{ $r->product->name }}</span>@endcan
                        <span>{{ $item->formatQuantity($r->quantity) }} each</span>
                    </div>
                @empty
                    <p class="small text-secondary mb-0">Not used in any recipe.</p>
                @endforelse
            </x-card>
        </div>
    </div>
    @include('admin.inventory._adjust-modal')
</x-layouts.admin>
