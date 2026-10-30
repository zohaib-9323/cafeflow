<x-layouts.admin title="Categories">
    <x-page-header title="Categories" :breadcrumbs="['Categories']" subtitle="Menu sections, shown in sort order.">
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New category</a>
    </x-page-header>
    @error('category')<x-alert type="danger">{{ $message }}</x-alert>@enderror
    <div class="card">
        <x-table>
            <x-slot:head><th>Name</th><th>Slug</th><th class="text-end">Products</th><th>Status</th><th class="text-end">Order</th><th></th></x-slot:head>
            @forelse ($categories as $category)
                <tr>
                    <td data-label="Name" class="fw-semibold">{{ $category->name }}</td>
                    <td data-label="Slug"><code>{{ $category->slug }}</code></td>
                    <td data-label="Products" class="text-end">{{ $category->products_count }}</td>
                    <td data-label="Status"><x-badge :tone="$category->is_active ? 'success' : 'secondary'">{{ $category->is_active ? 'Visible' : 'Hidden' }}</x-badge></td>
                    <td data-label="Order" class="text-end">{{ $category->sort_order }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-light">Edit</a>
                        <x-confirm :action="route('admin.categories.destroy', $category)" method="DELETE" class="d-inline" :message="'Delete the '.$category->name.' category?'" confirm-label="Delete">
                            <button class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $category->name }}" @disabled($category->products_count > 0) @if ($category->products_count) title="Has products" @endif><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </x-confirm>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty-state icon="tags" title="No categories yet" /></td></tr>
            @endforelse
        </x-table>
    </div>
</x-layouts.admin>
