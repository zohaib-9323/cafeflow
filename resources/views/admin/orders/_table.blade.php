@php use App\Support\Money; @endphp
<x-table>
    <x-slot:head>
        <th>Order</th><th>Customer</th><th>Type</th><th>Status</th><th>Payment</th><th>Assigned</th><th class="text-end">Total</th>
    </x-slot:head>
    @forelse ($orders as $order)
        <tr>
            <td data-label="Order"><a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold">{{ $order->number }}</a>
                <span class="d-block small text-secondary">{{ $order->placed_at->format('M j, g:i A') }} · {{ $order->items_count }} {{ Str::plural('item', $order->items_count) }}</span></td>
            <td data-label="Customer">{{ $order->customer_name }}<span class="d-block small text-secondary">{{ $order->customer_phone }}</span></td>
            <td data-label="Type"><i class="bi bi-{{ $order->fulfillment_type->value === 'pickup' ? 'shop' : 'bicycle' }} me-1" aria-hidden="true"></i>{{ $order->fulfillment_type->label() }}</td>
            <td data-label="Status"><x-status-badge :status="$order->status" /></td>
            <td data-label="Payment"><span class="small">{{ ucfirst($order->payment_method->value) }}</span> <x-badge :tone="$order->payment_status->tone()">{{ $order->payment_status->label() }}</x-badge></td>
            <td data-label="Assigned" class="small">{{ $order->assignee?->name ?? '—' }}</td>
            <td data-label="Total" class="text-end fw-medium">{{ Money::format($order->total) }}</td>
        </tr>
    @empty
        <tr><td colspan="7"><x-empty-state icon="search" title="No orders match these filters" /></td></tr>
    @endforelse
</x-table>
@if ($orders->hasPages())<div class="card-footer bg-transparent"><x-pagination :paginator="$orders" /></div>@endif
