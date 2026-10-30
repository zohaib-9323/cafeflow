@php use App\Support\Money; @endphp
<x-layouts.site title="My orders">
    <div class="container py-5">
        <h1 class="section-title mb-3">My account</h1>
        @include('account._nav')

        @if ($orders->isEmpty())
            <x-empty-state icon="receipt" title="No orders yet" message="When you order, you can follow it here.">
                <a href="{{ route('menu.index') }}" class="btn btn-primary">Start an order</a>
            </x-empty-state>
        @else
            <div class="surface">
                <x-table>
                    <x-slot:head><th>Order</th><th>Placed</th><th>Type</th><th>Status</th><th class="text-end">Total</th><th><span class="visually-hidden">Actions</span></th></x-slot:head>
                    @foreach ($orders as $order)
                        <tr>
                            <td data-label="Order" class="fw-semibold">{{ $order->number }}<span class="d-block small text-secondary fw-normal">{{ $order->items_count }} {{ Str::plural('item', $order->items_count) }}</span></td>
                            <td data-label="Placed">{{ $order->placed_at->format('M j, Y · g:i A') }}</td>
                            <td data-label="Type">{{ $order->fulfillment_type->label() }}</td>
                            <td data-label="Status"><x-status-badge :status="$order->status" /></td>
                            <td data-label="Total" class="text-end">{{ Money::format($order->total) }}</td>
                            <td data-label="" class="text-end"><a href="{{ route('account.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">{{ $order->status->isActive() ? 'Track' : 'View' }}</a></td>
                        </tr>
                    @endforeach
                </x-table>
            </div>
            <x-pagination :paginator="$orders" />
        @endif
    </div>
</x-layouts.site>
