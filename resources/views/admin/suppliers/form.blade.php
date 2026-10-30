@php $editing = $supplier->exists; @endphp
<x-layouts.admin :title="$editing ? 'Edit '.$supplier->name : 'New supplier'">
    <x-page-header :title="$editing ? $supplier->name : 'New supplier'" :breadcrumbs="['Suppliers' => route('admin.suppliers.index'), $editing ? 'Edit' : 'New']" />
    <div class="row"><div class="col-lg-7"><x-card>
        <form method="POST" action="{{ $editing ? route('admin.suppliers.update', $supplier) : route('admin.suppliers.store') }}" novalidate>
            @csrf @if ($editing) @method('PUT') @endif
            <x-input name="name" label="Company name" :value="$supplier->name" required maxlength="150" />
            <div class="row">
                <div class="col-md-6"><x-input name="contact_name" label="Contact person" :value="$supplier->contact_name" maxlength="100" /></div>
                <div class="col-md-6"><x-input name="lead_time_days" label="Lead time (days)" type="number" min="0" :value="$supplier->lead_time_days" /></div>
                <div class="col-md-6"><x-input name="email" label="Email" type="email" :value="$supplier->email" /></div>
                <div class="col-md-6"><x-input name="phone" label="Phone" type="tel" :value="$supplier->phone" maxlength="32" /></div>
            </div>
            <x-input name="address" label="Address" :value="$supplier->address" maxlength="255" />
            <x-textarea name="notes" label="Notes" :value="$supplier->notes" rows="3" maxlength="2000" />
            <x-checkbox name="is_active" label="Active" :checked="$supplier->is_active" />
            <div class="d-flex gap-2"><button class="btn btn-primary">{{ $editing ? 'Save' : 'Add supplier' }}</button><a href="{{ route('admin.suppliers.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </x-card></div></div>
</x-layouts.admin>
