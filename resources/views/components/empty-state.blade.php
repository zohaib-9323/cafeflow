@props(['icon' => 'inbox', 'title', 'message' => null])
<div {{ $attributes->class('empty-state') }}>
    <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
    <p class="fw-semibold text-body mb-1">{{ $title }}</p>
    @if ($message)<p class="mb-3">{{ $message }}</p>@endif
    {{ $slot }}
</div>
