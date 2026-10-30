@php use App\Support\Money; $biz = app(\App\Services\SiteContent::class)->all(); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $order->number }}</title>
    @vite(['resources/scss/admin.scss', 'resources/js/admin.js'])
</head>
<body class="bg-white p-4">
    <div class="receipt">
        <div class="text-center mb-3">
            <strong class="fs-5">{{ $biz['business']['name'] }}</strong><br>
            {{ $biz['contact']['address'] }}, {{ $biz['contact']['city'] }}<br>{{ $biz['contact']['phone'] }}
        </div>
        <div class="border-top border-bottom border-dark py-2 mb-2">
            Order: <strong>{{ $order->number }}</strong><br>
            {{ $order->placed_at->format('Y-m-d H:i') }} · {{ strtoupper($order->fulfillment_type->value) }}<br>
            {{ $order->customer_name }} · {{ $order->customer_phone }}
            @if ($order->deliveryAddress())<br>{{ $order->deliveryAddress() }}@endif
        </div>
        <table class="w-100 mb-2">
            @foreach ($order->items as $item)
                <tr><td>{{ $item->quantity }}× {{ $item->product_name }}@if ($item->variation_name) ({{ $item->variation_name }})@endif
                    @foreach ($item->addons as $a)<br>&nbsp;&nbsp;+ {{ $a->name }}@endforeach</td>
                    <td class="text-end align-top">{{ Money::format($item->line_total) }}</td></tr>
            @endforeach
        </table>
        <table class="w-100 border-top border-dark pt-2">
            <tr><td>Subtotal</td><td class="text-end">{{ Money::format($order->subtotal) }}</td></tr>
            @if ((float) $order->discount_total)<tr><td>Discount</td><td class="text-end">-{{ Money::format($order->discount_total) }}</td></tr>@endif
            <tr><td>Tax</td><td class="text-end">{{ Money::format($order->tax_total) }}</td></tr>
            @if ((float) $order->delivery_fee)<tr><td>Delivery</td><td class="text-end">{{ Money::format($order->delivery_fee) }}</td></tr>@endif
            <tr class="fw-bold"><td>TOTAL</td><td class="text-end">{{ Money::format($order->total) }}</td></tr>
            <tr><td>Payment</td><td class="text-end">{{ strtoupper($order->payment_method->value) }} · {{ strtoupper($order->payment_status->value) }}</td></tr>
        </table>
        @if ($order->notes)<p class="mt-2 mb-0">Note: {{ $order->notes }}</p>@endif
        <p class="text-center mt-3">Thank you!</p>
        <div class="text-center no-print"><button class="btn btn-primary btn-sm" data-print><i class="bi bi-printer me-1" aria-hidden="true"></i>Print</button></div>
    </div>
</body>
</html>
