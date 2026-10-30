@php $editing = $item->exists; @endphp
<x-layouts.admin :title="$editing ? 'Edit '.$item->name : 'New inventory item'">
    <x-page-header :title="$editing ? $item->name : 'New inventory item'" :breadcrumbs="['Inventory' => route('admin.inventory.index'), $editing ? 'Edit' : 'New']" />
    <div class="row"><div class="col-lg-7">
        <x-card>
            <form method="POST" action="{{ $editing ? route('admin.inventory.update', $item) : route('admin.inventory.store') }}" novalidate>
                @csrf @if ($editing) @method('PUT') @endif
                <div class="row">
                    <div class="col-md-8"><x-input name="name" label="Name" :value="$item->name" required maxlength="150" /></div>
                    <div class="col-md-4"><x-input name="sku" label="SKU" :value="$item->sku" required maxlength="40" /></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><x-select name="unit" label="Unit" :options="collect($units)->mapWithKeys(fn ($u) => [$u->value => $u->label()])" :value="$item->unit" required /></div>
                    <div class="col-md-6"><x-select name="supplier_id" label="Supplier" :options="$suppliers->pluck('name', 'id')" :value="$item->supplier_id" placeholder="None" /></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><x-input name="low_stock_threshold" label="Reorder at" type="number" step="0.001" min="0" :value="$item->low_stock_threshold !== null ? (float) $item->low_stock_threshold : null" required help="Managers are alerted at or below this level." /></div>
                    <div class="col-md-6"><x-input name="cost_per_unit" label="Cost per unit" type="number" step="0.0001" min="0" :value="$item->cost_per_unit !== null ? (float) $item->cost_per_unit : null" prefix="$" /></div>
                </div>
                @if ($editing)
                    <p class="small text-secondary">Current stock: <strong>{{ $item->formatQuantity() }}</strong>. Change it with <a href="{{ route('admin.inventory.show', $item) }}">Record change</a> so it is logged.</p>
                @else
                    <x-input name="opening_quantity" label="Opening stock" type="number" step="0.001" min="0" help="Recorded as a restock movement." />
                @endif
                <x-checkbox name="allow_negative" label="Allow stock to go below zero" :checked="$item->allow_negative" help="Use only for items you don't count precisely (e.g. ice)." />
                <div class="d-flex gap-2"><button class="btn btn-primary">{{ $editing ? 'Save' : 'Add item' }}</button><a href="{{ route('admin.inventory.index') }}" class="btn btn-light">Cancel</a></div>
            </form>
        </x-card>
    </div></div>
</x-layouts.admin>
