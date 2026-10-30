<x-mail::message>
# Thanks, {{ $order->customer_name }}!

We've received order **{{ $order->number }}** for **{{ $order->fulfillment_type->label() }}**.
@if ($awaitingPayment)
We'll start preparing it as soon as your online payment goes through.
@else
We'll email you as it moves along.
@endif

<x-mail::table>
| Item | Qty | Total |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->product_name }}@if($item->variation_name) ({{ $item->variation_name }})@endif @if($item->addons->isNotEmpty())<br><small>+ {{ $item->addons->pluck('name')->implode(', ') }}</small>@endif | {{ $item->quantity }} | {{ \App\Support\Money::format($item->line_total) }} |
@endforeach
| Subtotal | | {{ \App\Support\Money::format($order->subtotal) }} |
@if ((float) $order->discount_total > 0)
| Discount | | -{{ \App\Support\Money::format($order->discount_total) }} |
@endif
| Tax | | {{ \App\Support\Money::format($order->tax_total) }} |
@if ((float) $order->delivery_fee > 0)
| Delivery | | {{ \App\Support\Money::format($order->delivery_fee) }} |
@endif
| **Total** | | **{{ \App\Support\Money::format($order->total) }}** |
</x-mail::table>

@if ($order->deliveryAddress())
**Delivering to:** {{ $order->deliveryAddress() }}
@endif

<x-mail::button :url="$url">
Track your order
</x-mail::button>

See you soon,<br>
{{ config('cafeflow.business_name') }}
</x-mail::message>
