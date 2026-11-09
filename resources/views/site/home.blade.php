@php
    use App\Support\Money;
    $hero = $site['hero'];
    $heroImage = app(\App\Services\SiteContent::class)->imageUrl($hero['image'] ?? null, 'images/hero-default.jpg');
    $aboutImage = app(\App\Services\SiteContent::class)->imageUrl($site['about']['image'] ?? null, 'images/about-default.jpg');
    $lead = $featured->first();
    $rest = $featured->slice(1, 6);
    $phoneHref = 'tel:'.preg_replace('/[^0-9+]/', '', $site['contact']['phone']);
@endphp
<x-layouts.site :title="$site['business']['tagline']">
    {{-- Hero: one headline, one button. --}}
    <section class="hero" aria-labelledby="hero-title">
        <div class="hero-media">
            <img src="{{ $heroImage }}" alt="" fetchpriority="high" width="1600" height="900">
        </div>
        <div class="container hero-inner">
            <div class="hero-content">
                @if ($hero['eyebrow'])<p class="eyebrow mb-4">{{ $hero['eyebrow'] }}</p>@endif
                <h1 id="hero-title" class="mb-4 text-balance">{{ $hero['heading'] }}</h1>
                <p class="lead mb-5">{{ $hero['description'] }}</p>
                <div class="d-flex flex-wrap align-items-center gap-4">
                    <a href="{{ $hero['cta_url'] }}" class="btn btn-accent btn-lg">{{ $hero['cta_label'] }} <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
                    @if ($hero['secondary_label'] && $hero['secondary_url'])
                        <a href="{{ $hero['secondary_url'] }}" class="link-quiet text-white">{{ $hero['secondary_label'] }}</a>
                    @endif
                </div>
            </div>
            <dl class="hero-facts">
                @if ($row = $site['hours'][0] ?? null)
                    <div><dt>{{ $row['days'] }}</dt><dd>{{ $row['hours'] }}</dd></div>
                @endif
                <div><dt>{{ $site['contact']['address'] }}</dt><dd>{{ $site['contact']['city'] }}</dd></div>
                <div class="d-none d-md-block"><dt>Call ahead</dt><dd><a href="{{ $phoneHref }}">{{ $site['contact']['phone'] }}</a></dd></div>
            </dl>
        </div>
    </section>

    {{-- Favourites: one lead dish, the rest as a menu board. --}}
    @if ($lead)
        <section class="section" aria-labelledby="featured-title">
            <div class="container">
                <header class="section-head">
                    <div>
                        <p class="eyebrow mb-2">Favourites</p>
                        <h2 id="featured-title" class="section-title mb-0">{{ $site['home']['featured_heading'] }}</h2>
                    </div>
                    <a href="{{ route('menu.index') }}" class="link-quiet">See the full menu <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </header>
                <div class="row g-4 g-lg-5">
                    <div class="col-lg-6">
                        <a href="{{ route('menu.show', $lead) }}" class="lead-dish">
                            <img src="{{ $lead->imageUrl() }}" alt="{{ $lead->name }}" loading="lazy" width="900" height="1000">
                            <span class="lead-dish-body">
                                <span class="small text-uppercase opacity-75 d-block mb-1">{{ $lead->category?->name }}</span>
                                <span class="lead-dish-name">{{ $lead->name }}</span>
                                <span class="d-block opacity-75 mt-1">{{ $lead->short_description }}</span>
                            </span>
                            <span class="lead-dish-price">{{ Money::format($lead->price) }}</span>
                        </a>
                    </div>
                    @if ($rest->isNotEmpty())
                        <div class="col-lg-6">
                            <ul class="menu-board">
                                @foreach ($rest as $product)
                                    <x-menu-row :product="$product" :tag="false" />
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- Categories --}}
    @if ($categories->isNotEmpty())
        <section class="section pt-0" aria-labelledby="categories-title">
            <div class="container">
                <header class="section-head">
                    <div>
                        <p class="eyebrow mb-2">Menu</p>
                        <h2 id="categories-title" class="section-title mb-0">{{ $site['home']['categories_heading'] }}</h2>
                    </div>
                </header>
                <div class="category-strip">
                    @foreach ($categories as $category)
                        <a href="{{ route('menu.index', ['category' => $category->slug]) }}" class="category-tile">
                            <img src="{{ $category->image_path ? Storage::disk('public')->url($category->image_path) : asset('images/placeholder-product.svg') }}" alt="" loading="lazy" width="400" height="500">
                            <span class="label">{{ $category->name }}<span class="d-block small fw-normal font-sans opacity-75 mt-1">{{ $category->products_count }} {{ Str::plural('item', $category->products_count) }}</span></span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Intro --}}
    <section class="section bg-espresso" aria-labelledby="about-title">
        <div class="container">
            <div class="row align-items-center g-4 g-lg-5">
                <div class="col-lg-6">
                    <img src="{{ $aboutImage }}" alt="Inside {{ $site['business']['name'] }}" class="img-fluid rounded-4 w-100 object-fit-cover" style="aspect-ratio: 4/3" loading="lazy" width="960" height="720">
                </div>
                <div class="col-lg-5 offset-lg-1">
                    <p class="eyebrow mb-3">Our café</p>
                    <h2 id="about-title" class="section-title mb-4">{{ $site['about']['heading'] }}</h2>
                    <p class="fs-5 opacity-75 mb-4">{{ $site['about']['body'] }}</p>
                    <ul class="fact-list mb-4">
                        <li><strong>Ready in ~10 min</strong><span>Order ahead and skip the queue</span></li>
                        <li><strong>Local delivery</strong><span>Free over {{ Money::format(config('cafeflow.free_delivery_over')) }}</span></li>
                    </ul>
                    <a href="{{ route('pages.about') }}" class="link-quiet text-white">Read our story <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
    </section>

    {{-- Offers & banners --}}
    @if ($banners->isNotEmpty() || $offers->isNotEmpty())
        <section class="section" aria-labelledby="offers-title">
            <div class="container">
                <h2 id="offers-title" class="visually-hidden">Special offers</h2>
                @foreach ($banners as $banner)
                    <div class="promo-banner row g-0 mb-4">
                        @if ($banner->imageUrl())
                            <div class="col-md-6 order-md-2"><img src="{{ $banner->imageUrl() }}" alt="" loading="lazy" width="960" height="720"></div>
                        @endif
                        <div class="col-md-6 p-4 p-lg-5 d-flex flex-column justify-content-center">
                            <p class="eyebrow mb-3">Now at {{ $site['business']['name'] }}</p>
                            <h3 class="promo-title mb-3">{{ $banner->title }}</h3>
                            @if ($banner->subtitle)<p class="text-secondary mb-4">{{ $banner->subtitle }}</p>@endif
                            @if ($banner->cta_url)<div><a href="{{ $banner->cta_url }}" class="btn btn-accent">{{ $banner->cta_label }}</a></div>@endif
                        </div>
                    </div>
                @endforeach
                @if ($offers->isNotEmpty())
                    <div class="row g-3">
                        @foreach ($offers as $offer)
                            <div class="col-md">
                                <div class="ticket h-100">
                                    <div>
                                        <h3 class="h6 mb-1">{{ $offer->name }}</h3>
                                        <p class="small text-secondary mb-0">{{ $offer->description ?: $offer->summary() }}@if ($offer->expires_at) · until {{ $offer->expires_at->format('M j') }}@endif</p>
                                    </div>
                                    <span class="ticket-code">{{ $offer->code }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- Testimonials --}}
    @if ($testimonials->isNotEmpty())
        <section class="section pt-0" aria-labelledby="testimonials-title">
            <div class="container">
                <header class="section-head">
                    <div>
                        <p class="eyebrow mb-2">Reviews</p>
                        <h2 id="testimonials-title" class="section-title mb-0">{{ $site['home']['testimonials_heading'] }}</h2>
                    </div>
                </header>
                <div class="row g-4 g-lg-5">
                    @foreach ($testimonials->take(3) as $t)
                        <div class="col-md-4">
                            <figure class="testimonial mb-0">
                                <x-stars :rating="$t->rating" class="d-block mb-3" />
                                <blockquote class="mb-4">“{{ $t->quote }}”</blockquote>
                                <figcaption>
                                    <strong class="d-block">{{ $t->author_name }}</strong>
                                    <span class="small text-secondary">{{ $t->author_title }}</span>
                                </figcaption>
                            </figure>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Visit --}}
    <section class="section visit" aria-labelledby="visit-title">
        <div class="container">
            <div class="row g-4 g-lg-5">
                <div class="col-lg-6">
                    <p class="eyebrow mb-2">Visit us</p>
                    <h2 id="visit-title" class="section-title mb-4">Come say hello</h2>
                    <address class="fs-5 mb-4">{{ $site['contact']['address'] }}<br>{{ $site['contact']['city'] }}</address>
                    <p class="mb-1"><a href="{{ $phoneHref }}">{{ $site['contact']['phone'] }}</a></p>
                    <p class="mb-4"><a href="mailto:{{ $site['contact']['email'] }}">{{ $site['contact']['email'] }}</a></p>
                    @if ($site['contact']['map_url'])<a href="{{ $site['contact']['map_url'] }}" class="link-quiet" target="_blank" rel="noopener noreferrer">Get directions <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>@endif
                </div>
                <div class="col-lg-6">
                    <h3 class="eyebrow mb-3">Opening hours</h3>
                    <dl class="hours mb-0">
                        @foreach ($site['hours'] as $row)
                            <div><dt>{{ $row['days'] }}</dt><dd>{{ $row['hours'] }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
