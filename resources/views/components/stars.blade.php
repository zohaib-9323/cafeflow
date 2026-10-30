@props(['rating' => 0])
<span {{ $attributes->class('stars') }} role="img" aria-label="{{ round($rating, 1) }} out of 5 stars">
    @for ($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $rating >= $i ? '-fill' : ($rating >= $i - 0.5 ? '-half' : '') }}" aria-hidden="true"></i>@endfor
</span>
