<x-layouts.admin title="Reviews">
    <x-page-header title="Reviews" :breadcrumbs="['Reviews']" subtitle="Customer reviews are published only after approval." />
    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a class="nav-link @if ($status === 'pending') active @endif" href="{{ route('admin.reviews.index', ['status' => 'pending']) }}" @if ($status === 'pending') aria-current="page" @endif>Waiting <span class="badge text-bg-light ms-1">{{ $pendingCount }}</span></a></li>
        <li class="nav-item"><a class="nav-link @if ($status === 'approved') active @endif" href="{{ route('admin.reviews.index', ['status' => 'approved']) }}" @if ($status === 'approved') aria-current="page" @endif>Published</a></li>
    </ul>
    <div class="d-grid gap-3">
        @forelse ($reviews as $r)
            <x-card>
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                    <div>
                        <x-stars :rating="$r->rating" /> <strong class="ms-1">{{ $r->title }}</strong>
                        <p class="mb-1 mt-1">{{ $r->body }}</p>
                        <p class="small text-secondary mb-0">{{ $r->user?->name }} on
                            @if ($r->product)<a href="{{ route('menu.show', $r->product) }}" target="_blank" rel="noopener">{{ $r->product->name }}</a>@else a removed product @endif
                            · {{ $r->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="d-flex gap-2 align-items-start">
                        <form method="POST" action="{{ route('admin.reviews.update', $r) }}">@csrf @method('PATCH')
                            <input type="hidden" name="is_approved" value="{{ $r->is_approved ? 0 : 1 }}">
                            <button class="btn btn-sm {{ $r->is_approved ? 'btn-light' : 'btn-primary' }}">{{ $r->is_approved ? 'Unpublish' : 'Approve' }}</button>
                        </form>
                        <x-confirm :action="route('admin.reviews.destroy', $r)" method="DELETE" message="Delete this review permanently?" confirm-label="Delete">
                            <button class="btn btn-sm btn-outline-danger" aria-label="Delete review"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </x-confirm>
                    </div>
                </div>
            </x-card>
        @empty
            <x-card><x-empty-state icon="chat-square-heart" :title="$status === 'pending' ? 'No reviews waiting' : 'No published reviews'" /></x-card>
        @endforelse
    </div>
    @if ($reviews->hasPages())<div class="mt-3"><x-pagination :paginator="$reviews" /></div>@endif
</x-layouts.admin>
