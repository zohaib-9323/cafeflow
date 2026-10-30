@props(['product', 'headingLevel' => 3])
<article @class(['card product-card', 'is-unavailable' => ! $product->is_available])>
    <div class="ratio ratio-product overflow-hidden bg-beige">
        <img src="{{ $product->imageUrl() }}" alt="{{ ($product->relationLoaded('primaryImage') ? $product->primaryImage?->alt : null) ?: $product->name }}" class="product-img object-fit-cover" loading="lazy" decoding="async" width="600" height="450">
    </div>
    <div class="card-body d-flex flex-column p-3 p-lg-4">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
            <h{{ $headingLevel }} class="card-title mb-0">
                <a href="{{ route('menu.show', $product) }}" class="stretched-link text-reset text-decoration-none">{{ $product->name }}</a>
            </h{{ $headingLevel }}>
            <span class="price text-nowrap">{{ \App\Support\Money::format($product->price) }}</span>
        </div>
        <p class="text-secondary small mb-3 flex-grow-1">{{ $product->short_description }}</p>
        <div class="d-flex align-items-center justify-content-between small">
            <span class="text-secondary">{{ $product->category?->name }}</span>
            @if (! $product->is_available)
                <x-badge tone="secondary">Sold out today</x-badge>
            @elseif ($product->is_featured)
                <x-badge tone="accent" icon="star-fill">Favourite</x-badge>
            @endif
        </div>
    </div>
</article>
