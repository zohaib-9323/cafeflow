@props(['href', 'icon', 'active' => false, 'count' => null])
<a href="{{ $href }}" {{ $attributes->class(['nav-link', 'active' => $active]) }} @if ($active) aria-current="page" @endif title="{{ trim(strip_tags($slot)) }}">
    <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
    <span class="label flex-grow-1">{{ $slot }}</span>
    @if ($count)<span class="label badge rounded-pill text-bg-accent">{{ $count }}</span>@endif
</a>
