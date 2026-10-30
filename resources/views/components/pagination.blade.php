@props(['paginator'])
@if ($paginator->hasPages())
    <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2 mt-3">
        <div class="small text-secondary">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ number_format($paginator->total()) }}</div>
        {{-- Links only: the bootstrap-5 view repeats the "Showing…" summary. Same page-item/page-link markup. --}}
        {{ $paginator->onEachSide(1)->links('pagination::bootstrap-4') }}
    </div>
@endif
