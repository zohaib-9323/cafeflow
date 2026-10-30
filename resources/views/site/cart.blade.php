@php use App\Support\Money; @endphp
<x-layouts.site title="Your cart">
    <div class="container py-5">
        <h1 class="section-title mb-4">Your cart</h1>

        @if ($quote->isEmpty())
            @if ($quote->issues)
                <x-alert type="warning">@foreach ($quote->issues as $issue)<div>{{ $issue }}</div>@endforeach</x-alert>
            @endif
            <x-empty-state icon="bag" title="Your cart is empty" message="Find something good on the menu.">
                <a href="{{ route('menu.index') }}" class="btn btn-primary">Browse the menu</a>
            </x-empty-state>
        @else
            <div class="row g-4">
                <div class="col-lg-8">
                    <div @class(['alert alert-warning', 'd-none' => ! $quote->issues]) data-cart-issues-box role="status">
                        <ul class="mb-0 ps-3" data-cart-issues>@foreach ($quote->issues as $issue)<li>{{ $issue }}</li>@endforeach</ul>
                    </div>

                    <div class="surface">
                        <ul class="list-unstyled mb-0">
                            @foreach ($quote->lines as $line)
                                <li class="d-flex gap-3 p-3 {{ $loop->last ? '' : 'border-bottom' }}" data-cart-line="{{ $line->key }}" data-update-url="{{ route('cart.update', $line->key) }}">
                                    <img src="{{ $line->product->imageUrl() }}" alt="" class="rounded-3 object-fit-cover flex-shrink-0" width="88" height="88" loading="lazy">
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between gap-2">
                                            <h2 class="h6 mb-1"><a href="{{ route('menu.show', $line->product) }}" class="text-reset">{{ $line->product->name }}</a></h2>
                                            <strong class="text-nowrap" data-line-total="{{ $line->key }}">{{ Money::format($line->lineCents) }}</strong>
                                        </div>
                                        <div class="small text-secondary mb-2">
                                            @if ($line->variation){{ $line->variation->name }}@endif
                                            @if ($line->addons) · {{ collect($line->addons)->pluck('name')->implode(', ') }}@endif
                                            <span class="d-block">{{ Money::format($line->unitCents) }} each</span>
                                        </div>
                                        <form method="POST" action="{{ route('cart.update', $line->key) }}" class="d-flex align-items-center gap-3">
                                            @csrf @method('PATCH')
                                            <div class="input-group input-group-sm qty-control">
                                                <button class="btn btn-outline-secondary" type="button" data-qty-step="-1" aria-label="Decrease quantity of {{ $line->product->name }}"><i class="bi bi-dash" aria-hidden="true"></i></button>
                                                <label class="visually-hidden" for="q{{ $loop->index }}">Quantity of {{ $line->product->name }}</label>
                                                <input id="q{{ $loop->index }}" type="number" name="quantity" value="{{ $line->quantity }}" min="0" max="{{ config('cafeflow.max_item_quantity') }}" class="form-control">
                                                <button class="btn btn-outline-secondary" type="button" data-qty-step="1" aria-label="Increase quantity of {{ $line->product->name }}"><i class="bi bi-plus" aria-hidden="true"></i></button>
                                            </div>
                                            <noscript><button class="btn btn-sm btn-outline-primary">Update</button></noscript>
                                        </form>
                                    </div>
                                    <form method="POST" action="{{ route('cart.destroy', $line->key) }}" class="align-self-start">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-link text-secondary p-1" data-cart-remove aria-label="Remove {{ $line->product->name }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <a href="{{ route('menu.index') }}" class="btn btn-link px-0 mt-3"><i class="bi bi-arrow-left" aria-hidden="true"></i> Keep browsing</a>
                </div>

                <aside class="col-lg-4" aria-label="Order summary">
                    <div class="surface p-4 sticky-summary">
                        <h2 class="h5 mb-3">Summary</h2>
                        <fieldset class="mb-3">
                            <legend class="small fw-semibold mb-2">How would you like it?</legend>
                            <div class="btn-group w-100" role="group">
                                @foreach (\App\Enums\FulfillmentType::cases() as $type)
                                    <input type="radio" class="btn-check" name="fulfillment_type" id="ft_{{ $type->value }}" value="{{ $type->value }}" @checked($quote->fulfillment === $type)
                                           data-fulfillment-url="{{ route('cart.fulfillment') }}" data-summary-url="{{ route('cart.summary') }}">
                                    <label class="btn btn-outline-primary" for="ft_{{ $type->value }}"><i class="bi bi-{{ $type->value === 'pickup' ? 'shop' : 'bicycle' }} me-1" aria-hidden="true"></i>{{ $type->label() }}</label>
                                @endforeach
                            </div>
                            <p data-delivery-only @class(['small text-secondary mt-2 mb-0', 'd-none' => $quote->fulfillment->value !== 'delivery'])>
                                {{ Money::format(config('cafeflow.delivery_fee')) }} delivery, free over {{ Money::format(config('cafeflow.free_delivery_over')) }}.
                            </p>
                        </fieldset>

                        <form method="POST" action="{{ route('cart.promo') }}" class="mb-3">
                            @csrf
                            <label for="promo" class="form-label small fw-semibold">Promo code</label>
                            <div class="input-group has-validation">
                                <input id="promo" name="promo_code" class="form-control text-uppercase @error('promo_code') is-invalid @enderror" value="{{ $quote->promotion?->code }}" maxlength="40" autocomplete="off">
                                @if ($quote->promotion)
                                    <button class="btn btn-outline-secondary" name="promo_code" value="">Remove</button>
                                @else
                                    <button class="btn btn-outline-primary">Apply</button>
                                @endif
                            </div>
                        </form>

                        @include('site._summary')

                        <a href="{{ route('checkout.show') }}" @class(['btn btn-primary btn-lg w-100 mt-4', 'disabled' => ! $quote->canCheckout()]) data-checkout-button>
                            Checkout <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                        </a>
                        @guest<p class="small text-secondary text-center mt-2 mb-0">You'll sign in or create an account at checkout.</p>@endguest
                    </div>
                </aside>
            </div>
        @endif
    </div>
</x-layouts.site>
