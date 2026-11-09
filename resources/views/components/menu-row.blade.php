@props(['product', 'tag' => true])
{{-- One line of a printed-menu style list: thumb, name ···· price, description. Wrap in <ul class="menu-board">. --}}
<li @class(['is-unavailable' => ! $product->is_available])>
    <a href="{{ route('menu.show', $product) }}">
        <img src="{{ $product->imageUrl() }}" alt="" loading="lazy" width="160" height="160">
        <span class="menu-board-text">
            <span class="menu-board-line">
                <span class="menu-board-name">{{ $product->name }}</span>
                <span class="menu-board-dots" aria-hidden="true"></span>
                <span class="menu-board-price">{{ \App\Support\Money::format($product->price) }}</span>
            </span>
            <span class="small text-secondary d-block">
                @if (! $product->is_available)<strong class="text-body">Sold out today.</strong> @endif{{ $product->short_description }}
            </span>
            @if ($tag && $product->is_featured && $product->is_available)
                <span class="menu-board-tag"><i class="bi bi-star-fill" aria-hidden="true"></i> Favourite</span>
            @endif
        </span>
    </a>
</li>
