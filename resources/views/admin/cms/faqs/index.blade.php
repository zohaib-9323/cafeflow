<x-layouts.admin title="FAQs">
    <x-page-header title="FAQs" :breadcrumbs="['Content', 'FAQs']" subtitle="Use the arrows to reorder; the order saves automatically.">
        <a href="{{ route('admin.cms.faqs.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New question</a>
    </x-page-header>
    <div class="card">
        @if ($faqs->isEmpty())
            <div class="card-body"><x-empty-state icon="question-circle" title="No questions yet" /></div>
        @else
            <ol class="list-group list-group-flush list-group-numbered" data-sortable="{{ route('admin.cms.faqs.reorder') }}">
                @foreach ($faqs as $faq)
                    <li class="list-group-item d-flex gap-3 align-items-start" data-id="{{ $faq->id }}">
                        <div class="flex-grow-1">
                            <span class="fw-semibold">{{ $faq->question }}</span> @unless ($faq->is_published)<x-badge tone="warning">Hidden</x-badge>@endunless
                            <div class="small text-secondary">{{ Str::limit($faq->answer, 140) }}</div>
                        </div>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Reorder">
                            <button type="button" class="btn btn-light" data-move="up" aria-label="Move up"><i class="bi bi-arrow-up" aria-hidden="true"></i></button>
                            <button type="button" class="btn btn-light" data-move="down" aria-label="Move down"><i class="bi bi-arrow-down" aria-hidden="true"></i></button>
                        </div>
                        <a href="{{ route('admin.cms.faqs.edit', $faq) }}" class="btn btn-sm btn-light">Edit</a>
                        <x-confirm :action="route('admin.cms.faqs.destroy', $faq)" method="DELETE" message="Delete this question?" confirm-label="Delete">
                            <button class="btn btn-sm btn-outline-danger" aria-label="Delete question"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </x-confirm>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</x-layouts.admin>
