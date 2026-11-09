<x-layouts.site title="Menu" description="Coffee, bakery, breakfast and kitchen menu. Order ahead for pickup or delivery.">
    <section class="container page-head" aria-labelledby="menu-title">
        <div class="row align-items-end g-4">
            <div class="col-lg-7">
                <p class="eyebrow mb-3">Order ahead</p>
                <h1 id="menu-title" class="page-title mb-3">Our menu</h1>
                <p class="text-secondary fs-5 mb-0">Freshly made all day. Prices include everything except tax and delivery.</p>
            </div>
            <div class="col-lg-5">
                <form method="GET" action="{{ route('menu.index') }}" role="search" class="menu-search">
                    @if ($current)<input type="hidden" name="category" value="{{ $current->slug }}">@endif
                    <label for="menu-search" class="visually-hidden">Search the menu</label>
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input id="menu-search" type="search" name="q" value="{{ $term }}" class="form-control" placeholder="Search lattes, croissants…" maxlength="80">
                    <button class="btn btn-link link-quiet">Search</button>
                </form>
            </div>
        </div>
    </section>

    <div class="menu-filter">
        <nav class="container category-pills scroll-x d-flex gap-2" aria-label="Menu categories">
            <a href="{{ route('menu.index', array_filter(['q' => $term])) }}" @class(['btn btn-sm', 'active' => ! $current]) @if (! $current) aria-current="page" @endif>All</a>
            @foreach ($categories as $category)
                <a href="{{ route('menu.index', array_filter(['category' => $category->slug, 'q' => $term])) }}" @class(['btn btn-sm', 'active' => $current?->id === $category->id])
                   @if ($current?->id === $category->id) aria-current="page" @endif>{{ $category->name }}</a>
            @endforeach
        </nav>
    </div>

    <div class="container pb-5">
        @if ($term)
            <p class="text-secondary mt-4" role="status">{{ $products->count() }} {{ Str::plural('result', $products->count()) }} for “{{ $term }}”@if ($current) in {{ $current->name }}@endif.
                <a href="{{ route('menu.index', array_filter(['category' => $current?->slug])) }}">Clear search</a></p>
        @endif

        @foreach ($categories as $category)
            @continue(! $grouped->has($category->id))
            <section class="menu-chapter" aria-labelledby="cat-{{ $category->slug }}">
                <div class="row g-4 g-lg-5">
                    <div class="col-lg-4">
                        <div class="menu-chapter-head">
                            <p class="eyebrow mb-2">{{ $grouped[$category->id]->count() }} {{ Str::plural('item', $grouped[$category->id]->count()) }}</p>
                            <h2 id="cat-{{ $category->slug }}" class="section-title mb-2">{{ $category->name }}</h2>
                            @if ($category->description)<p class="text-secondary mb-0">{{ $category->description }}</p>@endif
                            @if ($category->image_path)
                                <img src="{{ Storage::disk('public')->url($category->image_path) }}" alt="" class="menu-chapter-img d-none d-lg-block" loading="lazy" width="400" height="500">
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <ul class="menu-board menu-board-lg">
                            @foreach ($grouped[$category->id] as $product)
                                <x-menu-row :product="$product" />
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>
        @endforeach

        @if ($products->isEmpty())
            <x-empty-state icon="search" title="Nothing matches that search" message="Try another word, or browse all categories.">
                <a href="{{ route('menu.index') }}" class="btn btn-primary">View the full menu</a>
            </x-empty-state>
        @endif

        @if ($banner && ! $term)
            <aside class="promo-banner row g-0 mt-5" aria-label="{{ $banner->title }}">
                @if ($banner->imageUrl())<div class="col-md-5 order-md-2"><img src="{{ $banner->imageUrl() }}" alt="" loading="lazy" width="960" height="720"></div>@endif
                <div class="col-md-7 p-4 p-lg-5 d-flex flex-column justify-content-center">
                    <h2 class="promo-title mb-2">{{ $banner->title }}</h2>
                    @if ($banner->subtitle)<p class="mb-4 text-secondary">{{ $banner->subtitle }}</p>@endif
                    @if ($banner->cta_url)<div><a href="{{ $banner->cta_url }}" class="btn btn-accent">{{ $banner->cta_label }}</a></div>@endif
                </div>
            </aside>
        @endif
    </div>
</x-layouts.site>
