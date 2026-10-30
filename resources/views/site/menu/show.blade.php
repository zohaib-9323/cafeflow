@php use App\Support\Money; @endphp
<x-layouts.site :title="$product->name" :description="$product->short_description">
    <div class="container py-4 py-lg-5">
        <nav aria-label="Breadcrumb" class="mb-3">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ route('menu.index') }}">Menu</a></li>
                <li class="breadcrumb-item"><a href="{{ route('menu.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
            </ol>
        </nav>

        <div class="row g-4 g-lg-5">
            <div class="col-lg-6">
                @php $first = $product->images->first(); @endphp
                <div class="gallery-main ratio ratio-4x3 mb-3">
                    <img src="{{ $first?->url() ?? asset('images/placeholder-product.svg') }}" alt="{{ $first?->alt ?: $product->name }}" class="object-fit-cover" data-gallery-main width="960" height="720">
                </div>
                @if ($product->images->count() > 1)
                    <div class="d-flex gap-2 flex-wrap" role="group" aria-label="More photos">
                        @foreach ($product->images as $image)
                            <button type="button" @class(['gallery-thumb', 'active' => $loop->first]) data-gallery-thumb data-src="{{ $image->url() }}" data-alt="{{ $image->alt ?: $product->name }}"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}" aria-label="Show photo {{ $loop->iteration }}">
                                <img src="{{ $image->url() }}" alt="" class="w-100 object-fit-cover" style="height:57px" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="col-lg-6">
                <p class="eyebrow mb-2">{{ $product->category->name }}</p>
                <h1 class="display-6 mb-2">{{ $product->name }}</h1>
                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                    <span class="fs-4 fw-semibold text-body">{{ Money::format($product->price) }}</span>
                    @if ($rating->count)
                        <a href="#reviews" class="small text-secondary text-decoration-none"><x-stars :rating="(float) $rating->avg" /> {{ number_format($rating->avg, 1) }} ({{ $rating->count }})</a>
                    @endif
                    <span class="small text-secondary"><i class="bi bi-stopwatch" aria-hidden="true"></i> ~{{ $product->prep_minutes }} min</span>
                    @if ($product->calories)<span class="small text-secondary">{{ $product->calories }} kcal</span>@endif
                </div>
                @if ($product->description)<p class="text-secondary mb-4">{{ $product->description }}</p>@endif

                @if (! $product->is_available)
                    <x-alert type="warning" :dismissible="false">This item is sold out for today. Please check back tomorrow.</x-alert>
                @else
                    <form method="POST" action="{{ route('cart.store') }}" data-cart-add data-price-calc data-base="{{ $product->price }}" class="surface p-4">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        @if ($product->variations->isNotEmpty())
                            <fieldset class="mb-4">
                                <legend class="fs-6 fw-semibold mb-2">Size</legend>
                                <div class="row g-2 option-tile">
                                    @foreach ($product->variations as $v)
                                        <div class="col-sm-4">
                                            <input type="radio" class="btn-check" name="variation_id" id="var{{ $v->id }}" value="{{ $v->id }}" data-adjust="{{ $v->price_adjustment }}" @checked($v->is_default || ($loop->first && ! $product->variations->contains('is_default', true)))>
                                            <label class="btn w-100 py-2" for="var{{ $v->id }}">
                                                <span class="d-block fw-medium">{{ $v->name }}</span>
                                                <span class="small text-secondary">{{ (float) $v->price_adjustment === 0.0 ? 'Included' : ((float) $v->price_adjustment > 0 ? '+' : '').Money::format($v->price_adjustment) }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endif

                        @if ($product->addons->isNotEmpty())
                            <fieldset class="mb-4">
                                <legend class="fs-6 fw-semibold mb-2">Extras <span class="fw-normal text-secondary small">(optional)</span></legend>
                                @foreach ($product->addons as $addon)
                                    <div class="form-check py-1">
                                        <input class="form-check-input" type="checkbox" name="addon_ids[]" value="{{ $addon->id }}" id="addon{{ $addon->id }}" data-price="{{ $addon->price }}">
                                        <label class="form-check-label d-flex justify-content-between" for="addon{{ $addon->id }}">
                                            <span>{{ $addon->name }}</span><span class="text-secondary">+{{ Money::format($addon->price) }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </fieldset>
                        @endif

                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div class="input-group qty-control">
                                <button class="btn btn-outline-secondary" type="button" data-qty-step="-1" aria-label="Decrease quantity"><i class="bi bi-dash" aria-hidden="true"></i></button>
                                <label for="qty" class="visually-hidden">Quantity</label>
                                <input type="number" class="form-control" id="qty" name="quantity" value="1" min="1" max="{{ config('cafeflow.max_item_quantity') }}" inputmode="numeric">
                                <button class="btn btn-outline-secondary" type="button" data-qty-step="1" aria-label="Increase quantity"><i class="bi bi-plus" aria-hidden="true"></i></button>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                                <i class="bi bi-bag-plus me-1" aria-hidden="true"></i> Add to cart · <span data-price-total aria-live="polite">{{ Money::format($product->price) }}</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <section id="reviews" class="mt-5 pt-3" aria-labelledby="reviews-title">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h2 id="reviews-title" class="h3">Reviews</h2>
                    @if ($rating->count)
                        <p class="mb-1"><x-stars :rating="(float) $rating->avg" /> <strong>{{ number_format($rating->avg, 1) }}</strong> out of 5</p>
                        <p class="text-secondary small">Based on {{ $rating->count }} {{ Str::plural('review', $rating->count) }} from verified customers.</p>
                    @else
                        <p class="text-secondary">No reviews yet.</p>
                    @endif

                    @if ($canReview)
                        <form method="POST" action="{{ route('reviews.store', $product) }}" class="surface p-3 mt-3">
                            @csrf
                            <h3 class="h6">Write a review</h3>
                            <x-select name="rating" label="Rating" :options="[5 => '5 – Excellent', 4 => '4 – Good', 3 => '3 – OK', 2 => '2 – Poor', 1 => '1 – Bad']" value="5" required />
                            <x-input name="title" label="Title" maxlength="120" />
                            <x-textarea name="body" label="Your review" rows="3" maxlength="2000" />
                            <button class="btn btn-primary btn-sm">Submit review</button>
                        </form>
                    @elseif (auth()->guest())
                        <p class="small text-secondary"><a href="{{ route('login') }}">Sign in</a> after ordering to leave a review.</p>
                    @endif
                </div>
                <div class="col-lg-8">
                    @foreach ($reviews as $review)
                        <article class="border-bottom py-3">
                            <div class="d-flex justify-content-between">
                                <x-stars :rating="$review->rating" />
                                <span class="small text-secondary">{{ $review->created_at->format('M j, Y') }}</span>
                            </div>
                            @if ($review->title)<h3 class="h6 mt-2 mb-1">{{ $review->title }}</h3>@endif
                            @if ($review->body)<p class="mb-1">{{ $review->body }}</p>@endif
                            <p class="small text-secondary mb-0">{{ Str::before($review->user->name, ' ') }} · verified purchase</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($related->isNotEmpty())
            <section class="mt-5" aria-labelledby="related-title">
                <h2 id="related-title" class="h3 mb-3">You might also like</h2>
                <div class="row g-4">
                    @foreach ($related as $item)
                        <div class="col-sm-6 col-lg-3"><x-product-card :product="$item" /></div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.site>
