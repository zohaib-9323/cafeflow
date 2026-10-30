<x-layouts.admin title="Testimonials">
    <x-page-header title="Testimonials" :breadcrumbs="['Content', 'Testimonials']" subtitle="Quotes from guests, shown on the home page.">
        <a href="{{ route('admin.cms.testimonials.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New testimonial</a>
    </x-page-header>
    <div class="row g-3">
        @forelse ($testimonials as $t)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100"><div class="card-body">
                    <x-stars :rating="$t->rating" />
                    <blockquote class="mt-2 mb-2">“{{ $t->quote }}”</blockquote>
                    <p class="small mb-0"><strong>{{ $t->author_name }}</strong>@if ($t->author_title), {{ $t->author_title }}@endif</p>
                </div>
                <div class="card-footer bg-transparent d-flex gap-2 align-items-center">
                    @unless ($t->is_published)<x-badge tone="warning">Hidden</x-badge>@endunless
                    <a href="{{ route('admin.cms.testimonials.edit', $t) }}" class="btn btn-sm btn-light ms-auto">Edit</a>
                    <x-confirm :action="route('admin.cms.testimonials.destroy', $t)" method="DELETE" message="Delete this testimonial?" confirm-label="Delete"><button class="btn btn-sm btn-outline-danger">Delete</button></x-confirm>
                </div></div>
            </div>
        @empty
            <div class="col-12"><x-card><x-empty-state icon="chat-quote" title="No testimonials yet" /></x-card></div>
        @endforelse
    </div>
</x-layouts.admin>
