@php
    use App\Support\Money;
    $hero = $site['hero'];
    $heroImage = app(\App\Services\SiteContent::class)->imageUrl($hero['image'] ?? null, 'images/hero-default.jpg');
    $aboutImage = app(\App\Services\SiteContent::class)->imageUrl($site['about']['image'] ?? null, 'images/about-default.jpg');
@endphp
<x-layouts.site :title="$site['business']['tagline']">
    <section class="hero mb-5" aria-labelledby="hero-title">
        <div class="hero-media">
            <img src="{{ $heroImage }}" alt="" fetchpriority="high" width="1600" height="900">
        </div>
        <div class="container">
            <div class="hero-content py-5">
                @if ($hero['eyebrow'])<p class="eyebrow mb-3">{{ $hero['eyebrow'] }}</p>@endif
                <h1 id="hero-title" class="mb-4 text-balance">{{ $hero['heading'] }}</h1>
                <p class="lead mb-4">{{ $hero['description'] }}</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ $hero['cta_url'] }}" class="btn btn-accent btn-lg px-4">{{ $hero['cta_label'] }} <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
                    @if ($hero['secondary_label'] && $hero['secondary_url'])
                        <a href="{{ $hero['secondary_url'] }}" class="btn btn-outline-light btn-lg px-4">{{ $hero['secondary_label'] }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Intro --}}
    <section class="section pt-4" aria-labelledby="about-title">
        <div class="container">
            <div class="row align-items-center g-4 g-lg-5">
                <div class="col-lg-6 order-lg-2">
                    <img src="{{ $aboutImage }}" alt="Inside {{ $site['business']['name'] }}" class="img-fluid rounded-4 shadow-sm w-100 object-fit-cover" style="aspect-ratio: 4/3" loading="lazy" width="960" height="720">
                </div>
                <div class="col-lg-6">
                    <p class="eyebrow mb-2">Our café</p>
                    <h2 id="about-title" class="section-title mb-3">{{ $site['about']['heading'] }}</h2>
                    <p class="text-secondary fs-5 mb-4">{{ $site['about']['body'] }}</p>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="surface p-3 h-100"><i class="bi bi-clock text-coffee me-2" aria-hidden="true"></i><strong>Ready in ~10 min</strong><div class="small text-secondary">Order ahead and skip the queue</div></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="surface p-3 h-100"><i class="bi bi-bicycle text-coffee me-2" aria-hidden="true"></i><strong>Local delivery</strong><div class="small text-secondary">Free over {{ Money::format(config('cafeflow.free_delivery_over')) }}</div></div>
                        </div>
                    </div>
                    <a href="{{ route('pages.about') }}" class="btn btn-outline-primary">Read our story</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Featured --}}
    @if ($featured->isNotEmpty())
        <section class="section bg-beige" aria-labelledby="featured-title">
            <div class="container">
                <div class="d-flex justify-content-between align-items-end mb-4 gap-3 flex-wrap">
                    <div>
                        <p class="eyebrow mb-2">Favourites</p>
                        <h2 id="featured-title" class="section-title mb-0">{{ $site['home']['featured_heading'] }}</h2>
                    </div>
                    <a href="{{ route('menu.index') }}" class="btn btn-link px-0">See the full menu <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="row g-4">
                    @foreach ($featured as $product)
                        <div class="col-sm-6 col-lg-3"><x-product-card :product="$product" /></div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Categories --}}
    @if ($categories->isNotEmpty())
        <section class="section" aria-labelledby="categories-title">
            <div class="container">
                <p class="eyebrow mb-2">Menu</p>
                <h2 id="categories-title" class="section-title mb-4">{{ $site['home']['categories_heading'] }}</h2>
                <div class="row g-3 g-lg-4">
                    @foreach ($categories as $category)
                        <div class="col-6 col-md-4 col-lg-2">
                            <a href="{{ route('menu.index', ['category' => $category->slug]) }}" class="category-tile">
                                <img src="{{ $category->image_path ? Storage::disk('public')->url($category->image_path) : asset('images/placeholder-product.svg') }}" alt="" loading="lazy" width="400" height="500">
                                <span class="label">{{ $category->name }}<span class="d-block small fw-normal font-sans opacity-75">{{ $category->products_count }} {{ Str::plural('item', $category->products_count) }}</span></span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Offers & banners --}}
    @if ($banners->isNotEmpty() || $offers->isNotEmpty())
        <section class="section pt-0" aria-labelledby="offers-title">
            <div class="container">
                <h2 id="offers-title" class="visually-hidden">Special offers</h2>
                @foreach ($banners as $banner)
                    <div class="promo-banner row g-0 mb-4">
                        <div class="col-md-6 p-4 p-lg-5 d-flex flex-column justify-content-center">
                            <p class="eyebrow text-accent mb-2">Now at {{ $site['business']['name'] }}</p>
                            <h3 class="display-6 mb-3">{{ $banner->title }}</h3>
                            @if ($banner->subtitle)<p class="opacity-75 mb-4">{{ $banner->subtitle }}</p>@endif
                            @if ($banner->cta_url)<div><a href="{{ $banner->cta_url }}" class="btn btn-accent">{{ $banner->cta_label }}</a></div>@endif
                        </div>
                        @if ($banner->imageUrl())
                            <div class="col-md-6"><img src="{{ $banner->imageUrl() }}" alt="" loading="lazy" width="960" height="720"></div>
                        @endif
                    </div>
                @endforeach
                @if ($offers->isNotEmpty())
                    <div class="row g-3">
                        @foreach ($offers as $offer)
                            <div class="col-md-4">
                                <div class="surface p-4 h-100 d-flex gap-3 align-items-start">
                                    <i class="bi bi-ticket-perforated fs-3 text-coffee" aria-hidden="true"></i>
                                    <div>
                                        <h3 class="h5 mb-1">{{ $offer->name }}</h3>
                                        <p class="small text-secondary mb-2">{{ $offer->description ?: $offer->summary() }}</p>
                                        <span class="badge text-bg-light border font-monospace fs-6">{{ $offer->code }}</span>
                                        @if ($offer->expires_at)<span class="small text-secondary ms-2">until {{ $offer->expires_at->format('M j') }}</span>@endif
                                    </div>
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
        <section class="section bg-beige" aria-labelledby="testimonials-title">
            <div class="container">
                <p class="eyebrow mb-2">Reviews</p>
                <h2 id="testimonials-title" class="section-title mb-4">{{ $site['home']['testimonials_heading'] }}</h2>
                <div class="row g-4">
                    @foreach ($testimonials->take(3) as $t)
                        <div class="col-md-4">
                            <figure class="testimonial mb-0">
                                <x-stars :rating="$t->rating" class="d-block mb-3" />
                                <blockquote class="mb-4">“{{ $t->quote }}”</blockquote>
                                <figcaption class="d-flex align-items-center gap-3">
                                    @if ($t->avatarUrl())
                                        <img src="{{ $t->avatarUrl() }}" alt="" class="rounded-circle object-fit-cover" width="44" height="44" loading="lazy">
                                    @else
                                        <span class="rounded-circle bg-primary text-white d-inline-grid fw-semibold" style="width:44px;height:44px;place-items:center" aria-hidden="true">{{ mb_substr($t->author_name, 0, 1) }}</span>
                                    @endif
                                    <span><strong class="d-block">{{ $t->author_name }}</strong><span class="small text-secondary">{{ $t->author_title }}</span></span>
                                </figcaption>
                            </figure>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Visit --}}
    <section class="section" aria-labelledby="visit-title">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5">
                    <p class="eyebrow mb-2">Visit us</p>
                    <h2 id="visit-title" class="section-title mb-4">Come say hello</h2>
                    <address class="fs-5 mb-4">{{ $site['contact']['address'] }}<br>{{ $site['contact']['city'] }}</address>
                    <p class="mb-1"><i class="bi bi-telephone me-2 text-coffee" aria-hidden="true"></i><a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact']['phone']) }}">{{ $site['contact']['phone'] }}</a></p>
                    <p><i class="bi bi-envelope me-2 text-coffee" aria-hidden="true"></i><a href="mailto:{{ $site['contact']['email'] }}">{{ $site['contact']['email'] }}</a></p>
                    @if ($site['contact']['map_url'])<a href="{{ $site['contact']['map_url'] }}" class="btn btn-outline-primary" target="_blank" rel="noopener noreferrer">Get directions</a>@endif
                </div>
                <div class="col-lg-6 offset-lg-1">
                    <div class="surface p-4 p-lg-5">
                        <h3 class="h4 mb-3"><i class="bi bi-clock me-2 text-coffee" aria-hidden="true"></i>Opening hours</h3>
                        <dl class="row mb-0">
                            @foreach ($site['hours'] as $row)
                                <dt class="col-6 fw-medium py-2 border-bottom">{{ $row['days'] }}</dt>
                                <dd class="col-6 text-end py-2 border-bottom mb-0">{{ $row['hours'] }}</dd>
                            @endforeach
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
