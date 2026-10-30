@props(['type' => 'info', 'dismissible' => true, 'icon' => null])
@php
    $icons = ['success' => 'check-circle', 'danger' => 'exclamation-octagon', 'warning' => 'exclamation-triangle', 'info' => 'info-circle'];
@endphp
<div {{ $attributes->class(["alert alert-{$type} d-flex align-items-start gap-2", 'alert-dismissible fade show' => $dismissible]) }} role="{{ $type === 'danger' ? 'alert' : 'status' }}">
    <i class="bi bi-{{ $icon ?? $icons[$type] ?? 'info-circle' }} mt-1" aria-hidden="true"></i>
    <div class="flex-grow-1">{{ $slot }}</div>
    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
    @endif
</div>
