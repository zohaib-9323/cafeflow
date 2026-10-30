<x-layouts.admin title="Media library">
    <x-page-header title="Media library" :breadcrumbs="['Content', 'Media']" subtitle="Every uploaded image. Files still in use can't be deleted." />
    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <form method="GET" role="search" class="d-flex gap-2">
                <label for="q" class="visually-hidden">Search media</label>
                <input type="search" id="q" name="q" value="{{ $term }}" class="form-control" placeholder="Search by file name or description">
                <button class="btn btn-light">Search</button>
            </form>
        </div>
        <div class="col-lg-5">
            <form method="POST" action="{{ route('admin.cms.media.store') }}" enctype="multipart/form-data" class="d-flex gap-2">
                @csrf
                <label for="files" class="visually-hidden">Images to upload</label>
                <input type="file" id="files" name="files[]" multiple accept="image/jpeg,image/png,image/webp" required class="form-control @error('files') is-invalid @enderror @error('files.*') is-invalid @enderror">
                <button class="btn btn-primary text-nowrap"><i class="bi bi-upload me-1" aria-hidden="true"></i>Upload</button>
            </form>
            @error('files.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            @error('media')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="row row-cols-2 row-cols-md-4 row-cols-xl-6 g-3">
        @forelse ($items as $m)
            @php $uses = $usage[$m->id] ?? []; @endphp
            <div class="col">
                <figure class="card h-100 mb-0">
                    <img src="{{ $m->url() }}" alt="{{ $m->alt }}" class="card-img-top object-fit-cover" height="120" loading="lazy">
                    <figcaption class="card-body p-2 small">
                        <div class="text-truncate" title="{{ $m->original_name }}">{{ $m->original_name }}</div>
                        <div class="text-secondary">{{ $m->width }}×{{ $m->height }} · {{ $m->humanSize() }}</div>
                        @if ($uses)<div class="text-secondary">Used in {{ implode(', ', $uses) }}</div>@endif
                    </figcaption>
                    <div class="card-footer bg-transparent p-2 d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-light flex-grow-1" data-copy="{{ $m->url() }}">Copy link</button>
                        @unless ($uses)
                            <x-confirm :action="route('admin.cms.media.destroy', $m)" method="DELETE" message="Delete this image permanently?" confirm-label="Delete">
                                <button class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $m->original_name }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                            </x-confirm>
                        @endunless
                    </div>
                </figure>
            </div>
        @empty
            <div class="col-12 w-100"><x-card><x-empty-state icon="images" title="No images yet" /></x-card></div>
        @endforelse
    </div>
    @if ($items->hasPages())<div class="mt-3"><x-pagination :paginator="$items" /></div>@endif
</x-layouts.admin>
