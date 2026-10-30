@props(['title' => null, 'subtitle' => null, 'flush' => false, 'icon' => null])
<section {{ $attributes->class('card') }} @if ($title) aria-labelledby="{{ $cid = 'card_'.\Illuminate\Support\Str::random(6) }}" @endif>
    @if ($title || isset($actions))
        <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <div>
                @if ($title)<h2 id="{{ $cid }}" class="h6 mb-0">@if ($icon)<i class="bi bi-{{ $icon }} me-2 text-coffee" aria-hidden="true"></i>@endif{{ $title }}</h2>@endif
                @if ($subtitle)<div class="small text-secondary">{{ $subtitle }}</div>@endif
            </div>
            @isset($actions)<div class="d-flex gap-2 flex-wrap">{{ $actions }}</div>@endisset
        </div>
    @endif
    @if ($flush)
        {{ $slot }}
    @else
        <div class="card-body">{{ $slot }}</div>
    @endif
    @isset($footer)<div class="card-footer bg-transparent">{{ $footer }}</div>@endisset
</section>
