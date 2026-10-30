@props(['status'])
{{-- Works for any enum with label() and tone(). --}}
<x-badge :tone="$status->tone()" {{ $attributes->merge(['data-status-badge' => $status->value]) }}>{{ $status->label() }}</x-badge>
