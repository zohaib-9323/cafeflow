@props(['variant' => 'primary', 'href' => null, 'icon' => null, 'type' => 'submit', 'size' => null])
@php $classes = 'btn btn-'.$variant.($size ? ' btn-'.$size : '').' d-inline-flex align-items-center justify-content-center gap-2'; @endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<i class="bi bi-{{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)<i class="bi bi-{{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot }}
    </button>
@endif
