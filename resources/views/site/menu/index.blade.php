<x-layouts.site title="Menu" description="Coffee, bakery, breakfast and kitchen menu. Order ahead for pickup or delivery.">
    <section class="container pt-5 pb-3">
        <div class="row align-items-end g-3">
            <div class="col-lg-7">
                <p class="eyebrow mb-2">Order ahead</p>
                <h1 class="section-title mb-2">Our menu</h1>
                <p class="text-secondary mb-0">Freshly made all day. Prices include everything except tax and delivery.</p>
            </div>
            <div class="col-lg-5">
                <form method="GET" action="{{ route('menu.index') }}" role="search" class="d-flex gap-2">
                    @if ($current)<input type="hidden" name="category" value="{{ $current->slug }}">@endif
                    <label for="menu-search" class="visually-hidden">Search the menu</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search" aria-hidden="true"></i></span>
                        <input id="menu-search" type="search" name="q" value="{{ $term }}" class="form-control border-start-0" placeholder="Search lattes, croissants…" maxlength="80">
                    </div>
                    <button class="btn btn-primary">Search</button>
                </form>
            </div>
        </div>

        <nav class="category-pills scroll-x d-flex gap-2 mt-4 pb-2" aria-label="Menu categories">
            <a href="{{ route('menu.index', array_filter(['q' => $term])) }}" @class(['btn btn-sm', 'active' => ! $current]) @if (! $current) aria-current="page" @endif>All</a>
            @foreach ($categories as $category)
                <a href="{{ route('menu.index', array_filter(['category' => $category->slug, 'q' => $term])) }}" @class(['btn btn-sm', 'active' => $current?->id === $category->id])
                   @if ($current?->id === $category->id) aria-current="page" @endif>{{ $category->name }}</a>
            @endforeach
        </nav>
    </section>

    @if ($banner && ! $term)
        <div class="container mb-4">
            <div class="promo-banner row g-0">
                <div class="col-md-7 p-4 d-flex flex-column justify-content-center">
                    <h2 class="h3 mb-1">{{ $banner->title }}</h2>
                    @if ($banner->subtitle)<p class="mb-3 text-secondary">{{ $banner->subtitle }}</p>@endif
                    @if ($banner->cta_url)<div><a href="{{ $banner->cta_url }}" class="btn btn-accent btn-sm">{{ $banner->cta_label }}</a></div>@endif
                </div>
                @if ($banner->imageUrl())<div class="col-md-5 d-none d-md-block"><img src="{{ $banner->imageUrl() }}" alt="" loading="lazy" style="max-height:220px"></div>@endif
            </div>
        </div>
    @endif

    <div class="container pb-5">
        @if ($term)
            <p class="text-secondary" role="status">{{ $products->count() }} {{ Str::plural('result', $products->count()) }} for “{{ $term }}”@if ($current) in {{ $current->name }}@endif.
                <a href="{{ route('menu.index', array_filter(['category' => $current?->slug])) }}">Clear search</a></p>
        @endif

        @forelse ($categories as $category)
            @continue(! $grouped->has($category->id))
            <section class="mb-5" aria-labelledby="cat-{{ $category->slug }}">
                <div class="mb-3">
                    <h2 id="cat-{{ $category->slug }}" class="h3 mb-1">{{ $category->name }}</h2>
                    @if ($category->description)<p class="text-secondary mb-0">{{ $category->description }}</p>@endif
                </div>
                <div class="row g-4">
                    @foreach ($grouped[$category->id] as $product)
                        <div class="col-sm-6 col-lg-4 col-xl-3"><x-product-card :product="$product" /></div>
                    @endforeach
                </div>
            </section>
        @empty
        @endforelse

        @if ($products->isEmpty())
            <x-empty-state icon="search" title="Nothing matches that search" message="Try another word, or browse all categories.">
                <a href="{{ route('menu.index') }}" class="btn btn-primary">View the full menu</a>
            </x-empty-state>
        @endif
    </div>
</x-layouts.site>
