<x-layouts.admin title="Suppliers">
    <x-page-header title="Suppliers" :breadcrumbs="['Inventory' => route('admin.inventory.index'), 'Suppliers']">
        <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New supplier</a>
    </x-page-header>
    <div class="card">
        <x-table>
            <x-slot:head><th>Supplier</th><th>Contact</th><th class="text-end">Lead time</th><th class="text-end">Items</th><th>Status</th><th></th></x-slot:head>
            @forelse ($suppliers as $s)
                <tr>
                    <td data-label="Supplier" class="fw-semibold">{{ $s->name }}</td>
                    <td data-label="Contact" class="small">{{ $s->contact_name }}
                        @if ($s->email)<a href="mailto:{{ $s->email }}" class="d-block">{{ $s->email }}</a>@endif
                        @if ($s->phone)<span class="d-block">{{ $s->phone }}</span>@endif</td>
                    <td data-label="Lead time" class="text-end">{{ $s->lead_time_days !== null ? $s->lead_time_days.' days' : '—' }}</td>
                    <td data-label="Items" class="text-end">{{ $s->inventory_items_count }}</td>
                    <td data-label="Status"><x-badge :tone="$s->is_active ? 'success' : 'secondary'">{{ $s->is_active ? 'Active' : 'Inactive' }}</x-badge></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.suppliers.edit', $s) }}" class="btn btn-sm btn-light">Edit</a>
                        <x-confirm :action="route('admin.suppliers.destroy', $s)" method="DELETE" class="d-inline" :message="'Remove '.$s->name.'? Linked ingredients keep their history.'" confirm-label="Remove">
                            <button class="btn btn-sm btn-outline-danger" aria-label="Remove {{ $s->name }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </x-confirm>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty-state icon="truck" title="No suppliers yet" /></td></tr>
            @endforelse
        </x-table>
        @if ($suppliers->hasPages())<div class="card-footer bg-transparent"><x-pagination :paginator="$suppliers" /></div>@endif
    </div>
</x-layouts.admin>
