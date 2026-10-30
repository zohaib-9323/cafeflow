@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentMethod;
    use App\Enums\PaymentStatus;
    use App\Support\Money;
    $stepIndex = array_search($order->status, $steps, true);
    $awaitingPayment = $order->payment_method === PaymentMethod::Card && in_array($order->payment_status, [PaymentStatus::Pending, PaymentStatus::Failed], true) && $order->status !== OrderStatus::Cancelled;
@endphp
<x-layouts.site :title="'Order '.$order->number">
    <div class="container py-5" @if ($order->status->isActive()) data-order-poll="{{ route('account.orders.status', $order) }}" data-status="{{ $order->status->value }}" data-payment-status="{{ $order->payment_status->value }}" @endif>
        <a href="{{ route('account.orders.index') }}" class="small"><i class="bi bi-arrow-left" aria-hidden="true"></i> All orders</a>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-2 mb-4">
            <div>
                <h1 class="section-title mb-1">Order {{ $order->number }}</h1>
                <p class="text-secondary mb-0">Placed {{ $order->placed_at->format('l, M j \a\t g:i A') }} · {{ $order->fulfillment_type->label() }}</p>
            </div>
            <x-status-badge :status="$order->status" class="fs-6 px-3 py-2" />
        </div>

        @if ($awaitingPayment)
            <x-alert type="warning" :dismissible="false">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>{{ $order->payment_status === PaymentStatus::Failed ? 'Your last payment attempt did not go through.' : 'This order is waiting for online payment.' }} We'll start preparing it once payment is received.</span>
                    <form method="POST" action="{{ route('account.orders.pay', $order) }}" data-once>@csrf<button class="btn btn-sm btn-primary">Pay {{ Money::format($order->total) }} now</button></form>
                </div>
            </x-alert>
        @endif

        <section class="surface p-4 p-lg-5 mb-4" aria-labelledby="track-title">
            <h2 id="track-title" class="h5 mb-4">Order progress</h2>
            @if ($order->status === OrderStatus::Cancelled)
                <x-alert type="secondary" :dismissible="false" icon="x-circle">
                    This order was cancelled {{ $order->cancelled_at?->diffForHumans() }}.@if ($order->cancellation_reason) Reason: {{ $order->cancellation_reason }}.@endif
                    @if ($order->payment_status === PaymentStatus::Refunded) Your payment has been refunded. @endif
                </x-alert>
            @else
                <ol class="tracker" aria-label="Order status">
                    @foreach ($steps as $i => $step)
                        @php $state = $i < $stepIndex ? 'done' : ($i === $stepIndex ? 'current' : ''); @endphp
                        <li class="{{ $state }}" @if ($state === 'current') aria-current="step" @endif>
                            <span class="step">@if ($state === 'done')<i class="bi bi-check-lg" aria-hidden="true"></i>@else{{ $i + 1 }}@endif</span>
                            <span>{{ $step->label() }}@if ($state === 'done')<span class="visually-hidden"> (done)</span>@endif</span>
                        </li>
                    @endforeach
                </ol>
                @if ($order->status->isActive())<p class="small text-secondary mt-4 mb-0"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> This page updates automatically.</p>@endif
            @endif
        </section>

        <div class="row g-4">
            <div class="col-lg-7">
                <section class="surface p-4" aria-labelledby="items-title">
                    <h2 id="items-title" class="h5 mb-3">Items</h2>
                    <ul class="list-unstyled mb-4">
                        @foreach ($order->items as $item)
                            <li class="d-flex justify-content-between gap-3 py-2 border-bottom">
                                <span><strong>{{ $item->quantity }}×</strong> {{ $item->product_name }}
                                    <span class="d-block small text-secondary">{{ collect([$item->variation_name, ...$item->addons->pluck('name')])->filter()->implode(' · ') }}</span></span>
                                <span>{{ Money::format($item->line_total) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <dl class="mb-0">
                        <div class="d-flex justify-content-between"><dt class="fw-normal text-secondary">Subtotal</dt><dd class="mb-1">{{ Money::format($order->subtotal) }}</dd></div>
                        @if ((float) $order->discount_total > 0)<div class="d-flex justify-content-between text-success"><dt class="fw-normal">Discount</dt><dd class="mb-1">-{{ Money::format($order->discount_total) }}</dd></div>@endif
                        <div class="d-flex justify-content-between"><dt class="fw-normal text-secondary">Tax</dt><dd class="mb-1">{{ Money::format($order->tax_total) }}</dd></div>
                        @if ($order->fulfillment_type->value === 'delivery')<div class="d-flex justify-content-between"><dt class="fw-normal text-secondary">Delivery</dt><dd class="mb-1">{{ Money::format($order->delivery_fee) }}</dd></div>@endif
                        <div class="d-flex justify-content-between fw-semibold fs-5 border-top pt-2 mt-2"><dt>Total</dt><dd class="mb-0">{{ Money::format($order->total) }}</dd></div>
                    </dl>
                </section>
            </div>
            <div class="col-lg-5">
                <section class="surface p-4 mb-4" aria-labelledby="details-title">
                    <h2 id="details-title" class="h5 mb-3">Details</h2>
                    <dl class="mb-0 small">
                        <dt>Payment</dt><dd>{{ $order->payment_method->label() }} · <x-badge :tone="$order->payment_status->tone()">{{ $order->payment_status->label() }}</x-badge></dd>
                        @if ($order->deliveryAddress())<dt>Delivering to</dt><dd>{{ $order->deliveryAddress() }}@if ($order->delivery_instructions)<br><span class="text-secondary">{{ $order->delivery_instructions }}</span>@endif</dd>
                        @else<dt>Pickup from</dt><dd>{{ $site['contact']['address'] }}, {{ $site['contact']['city'] }}</dd>@endif
                        <dt>Contact</dt><dd>{{ $order->customer_name }} · {{ $order->customer_phone }}</dd>
                        @if ($order->notes)<dt>Notes</dt><dd>{{ $order->notes }}</dd>@endif
                    </dl>
                </section>
                @can('cancelOwn', $order)
                    <form method="POST" action="{{ route('account.orders.cancel', $order) }}" class="text-end">
                        @csrf
                        <button class="btn btn-outline-danger btn-sm">Cancel this order</button>
                        <p class="small text-secondary mt-2">You can cancel until we confirm the order.</p>
                    </form>
                @endcan
            </div>
        </div>
    </div>
</x-layouts.site>
