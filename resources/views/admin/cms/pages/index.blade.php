<x-layouts.admin title="Pages">
    <x-page-header title="Pages" :breadcrumbs="['Content', 'Pages']" subtitle="About, policies and anything else that needs its own page.">
        <a href="{{ route('admin.cms.pages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New page</a>
    </x-page-header>
    <div class="card">
        <x-table>
            <x-slot:head><th>Title</th><th>Address</th><th>Status</th><th>Updated</th><th></th></x-slot:head>
            @forelse ($pages as $page)
                <tr>
                    <td data-label="Title" class="fw-semibold">{{ $page->title }}@if ($page->show_in_footer) <span class="small text-secondary fw-normal">· in footer</span>@endif</td>
                    <td data-label="Address">@if ($page->is_published)<a href="{{ route('pages.show', $page->slug) }}" target="_blank" rel="noopener">/pages/{{ $page->slug }}</a>@else<span class="text-secondary">/pages/{{ $page->slug }}</span>@endif</td>
                    <td data-label="Status"><x-badge :tone="$page->is_published ? 'success' : 'warning'">{{ $page->is_published ? 'Published' : 'Draft' }}</x-badge></td>
                    <td data-label="Updated" class="small">{{ $page->updated_at->diffForHumans() }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.cms.pages.edit', $page) }}" class="btn btn-sm btn-light">Edit</a>
                        <x-confirm :action="route('admin.cms.pages.destroy', $page)" method="DELETE" class="d-inline" :message="'Delete “'.$page->title.'”? Links to it will stop working.'" confirm-label="Delete">
                            <button class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $page->title }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </x-confirm>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><x-empty-state icon="file-earmark-text" title="No pages yet" /></td></tr>
            @endforelse
        </x-table>
    </div>
</x-layouts.admin>
