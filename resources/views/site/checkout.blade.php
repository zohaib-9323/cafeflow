@php
    use App\Support\Money;
    $isDelivery = old('fulfillment_type', $quote->fulfillment->value) === 'delivery';
@endphp
<x-layouts.site title="Checkout">
    <div class="container py-5">
        <h1 class="section-title mb-4">Checkout</h1>
        <form method="POST" action="{{ route('checkout.store') }}" class="row g-4" data-once novalidate>
            @csrf
            <div class="col-lg-7">
                <section class="surface p-4 mb-4" aria-labelledby="h-contact">
                    <h2 id="h-contact" class="h5 mb-3">1. Your details</h2>
                    <div class="row">
                        <div class="col-md-6"><x-input name="customer_name" label="Name" :value="$user->name" required autocomplete="name" /></div>
                        <div class="col-md-6"><x-input name="customer_phone" label="Phone" type="tel" :value="$user->customer?->phone" required autocomplete="tel" help="Only used about this order." /></div>
                    </div>
                    <p class="small text-secondary mb-0">Receipt goes to <strong>{{ $user->email }}</strong>.</p>
                </section>

                <section class="surface p-4 mb-4" aria-labelledby="h-fulfil">
                    <h2 id="h-fulfil" class="h5 mb-3">2. Pickup or delivery</h2>
                    <div class="row g-2 option-tile mb-3">
                        @foreach (\App\Enums\FulfillmentType::cases() as $type)
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="fulfillment_type" id="co_{{ $type->value }}" value="{{ $type->value }}"
                                       @checked(old('fulfillment_type', $quote->fulfillment->value) === $type->value)
                                       data-fulfillment-url="{{ route('cart.fulfillment') }}" data-summary-url="{{ route('cart.summary') }}">
                                <label class="btn w-100 py-3" for="co_{{ $type->value }}">
                                    <i class="bi bi-{{ $type->value === 'pickup' ? 'shop' : 'bicycle' }} fs-4 d-block mb-1" aria-hidden="true"></i>
                                    <span class="fw-medium">{{ $type->label() }}</span>
                                    <span class="d-block small text-secondary">{{ $type->value === 'pickup' ? 'Ready in about 10–15 min' : 'Usually 30–45 min' }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @error('fulfillment_type')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                    <div data-delivery-only @class(['d-none' => ! $isDelivery])>
                        @if ($addresses->isNotEmpty())
                            <label for="saved-address" class="form-label fw-medium">Saved addresses</label>
                            <select id="saved-address" class="form-select mb-3" data-saved-address>
                                <option value="">Choose a saved address…</option>
                                @foreach ($addresses as $a)
                                    <option value="{{ $a->id }}" data-address="{{ json_encode($a->only(['line1', 'line2', 'city', 'postal_code', 'instructions'])) }}">{{ $a->label }}: {{ $a->oneLine() }}</option>
                                @endforeach
                            </select>
                        @endif
                        @php $default = $addresses->firstWhere('is_default', true); @endphp
                        <x-input name="delivery_line1" id="f_delivery_line1" label="Street address" :value="$default?->line1" autocomplete="address-line1" data-required-when-delivery />
                        <x-input name="delivery_line2" id="f_delivery_line2" label="Apartment, floor (optional)" :value="$default?->line2" autocomplete="address-line2" />
                        <div class="row">
                            <div class="col-md-7"><x-input name="delivery_city" id="f_delivery_city" label="City" :value="$default?->city" autocomplete="address-level2" data-required-when-delivery /></div>
                            <div class="col-md-5"><x-input name="delivery_postal_code" id="f_delivery_postal_code" label="Postal code" :value="$default?->postal_code" autocomplete="postal-code" data-required-when-delivery /></div>
                        </div>
                        <x-input name="delivery_instructions" id="f_delivery_instructions" label="Delivery instructions (optional)" :value="$default?->instructions" maxlength="500" />
                        <x-checkbox name="save_address" label="Save this address for next time" :checked="$addresses->isEmpty()" :switch="false" />
                    </div>
                </section>

                <section class="surface p-4 mb-4" aria-labelledby="h-pay">
                    <h2 id="h-pay" class="h5 mb-3">3. Payment</h2>
                    @foreach (\App\Enums\PaymentMethod::cases() as $method)
                        <div class="form-check border rounded-3 p-3 ps-5 mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="pm_{{ $method->value }}" value="{{ $method->value }}" @checked(old('payment_method', 'card') === $method->value)>
                            <label class="form-check-label w-100" for="pm_{{ $method->value }}">
                                <span class="fw-medium">{{ $method->label() }}</span>
                                <span class="d-block small text-secondary">
                                    @if ($method->value === 'card')
                                        @if ($gateway === 'mock')
                                            Secure hosted checkout. <strong>Development test gateway:</strong> no real card is charged or entered.
                                        @else
                                            Secure hosted checkout by Stripe. Card details never touch our servers.
                                        @endif
                                    @else
                                        Pay when you collect or when the courier arrives.
                                    @endif
                                </span>
                            </label>
                        </div>
                    @endforeach
                    @error('payment_method')<div class="text-danger small">{{ $message }}</div>@enderror
                </section>

                <section class="surface p-4" aria-labelledby="h-notes">
                    <h2 id="h-notes" class="h5 mb-3">4. Anything else?</h2>
                    <x-textarea name="notes" label="Order notes (optional)" rows="2" maxlength="1000" help="Allergies, name for the cup, etc." wrapperClass="mb-0" />
                </section>
            </div>

            <aside class="col-lg-5" aria-label="Order summary">
                <div class="surface p-4 sticky-summary">
                    <h2 class="h5 mb-3">Your order</h2>
                    <ul class="list-unstyled mb-3">
                        @foreach ($quote->lines as $line)
                            <li class="d-flex justify-content-between gap-2 py-2 border-bottom">
                                <span><span class="fw-medium">{{ $line->quantity }}×</span> {{ $line->product->name }}
                                    <span class="d-block small text-secondary">{{ collect([$line->variation?->name, ...collect($line->addons)->pluck('name')])->filter()->implode(' · ') }}</span>
                                </span>
                                <span class="text-nowrap">{{ Money::format($line->lineCents) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @include('site._summary')
                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">Place order</button>
                    <p class="small text-secondary text-center mt-2 mb-0">Totals are recalculated securely when you place the order.
                        <a href="{{ route('pages.terms-and-conditions') }}">Terms</a> apply.</p>
                </div>
            </aside>
        </form>
    </div>
</x-layouts.site>
