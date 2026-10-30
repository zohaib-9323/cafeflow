@props(['tone' => 'secondary', 'icon' => null])
<span {{ $attributes->class("badge rounded-pill text-bg-{$tone}") }}>@if ($icon)<i class="bi bi-{{ $icon }} me-1" aria-hidden="true"></i>@endif{{ $slot }}</span>
