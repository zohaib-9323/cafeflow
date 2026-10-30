@props(['title', 'subtitle' => null, 'breadcrumbs' => []])
<div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
    <div>
        @if ($breadcrumbs)
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    @foreach ($breadcrumbs as $label => $url)
                        @if ($loop->last)
                            <li class="breadcrumb-item active" aria-current="page">{{ is_int($label) ? $url : $label }}</li>
                        @else
                            <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif
        <h1 class="mb-0">{{ $title }}</h1>
        @if ($subtitle)<p class="text-secondary mb-0 mt-1">{{ $subtitle }}</p>@endif
    </div>
    @if ($slot->isNotEmpty())<div class="d-flex gap-2 flex-wrap">{{ $slot }}</div>@endif
</div>
