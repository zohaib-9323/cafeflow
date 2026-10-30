<x-layouts.admin title="Banners">
    <x-page-header title="Banners" :breadcrumbs="['Content', 'Banners']" subtitle="Seasonal highlights shown on the home page or above the menu.">
        <a href="{{ route('admin.cms.banners.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New banner</a>
    </x-page-header>
    <div class="row g-3">
        @forelse ($banners as $b)
            @php $live = $b->is_active && (! $b->starts_at || $b->starts_at->isPast()) && (! $b->ends_at || $b->ends_at->isFuture()); @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    @if ($b->imageUrl())<img src="{{ $b->imageUrl() }}" alt="" class="card-img-top object-fit-cover" height="150">@endif
                    <div class="card-body">
                        <div class="d-flex gap-2 mb-2"><x-badge :tone="$live ? 'success' : 'secondary'">{{ $live ? 'Showing' : ($b->is_active ? 'Scheduled / ended' : 'Off') }}</x-badge><x-badge tone="light">{{ ucfirst($b->placement) }} page</x-badge></div>
                        <h2 class="h6 mb-1">{{ $b->title }}</h2>
                        <p class="small text-secondary mb-2">{{ $b->subtitle }}</p>
                        <p class="small mb-0">{{ $b->starts_at?->format('M j') ?? 'Now' }} – {{ $b->ends_at?->format('M j, Y') ?? 'until turned off' }}</p>
                    </div>
                    <div class="card-footer bg-transparent d-flex gap-2">
                        <a href="{{ route('admin.cms.banners.edit', $b) }}" class="btn btn-sm btn-light">Edit</a>
                        <x-confirm :action="route('admin.cms.banners.destroy', $b)" method="DELETE" message="Delete this banner?" confirm-label="Delete"><button class="btn btn-sm btn-outline-danger">Delete</button></x-confirm>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><x-card><x-empty-state icon="image" title="No banners" message="Add one for a seasonal drink or an event." /></x-card></div>
        @endforelse
    </div>
</x-layouts.admin>
