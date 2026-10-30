@php use App\Support\Money; @endphp
<x-layouts.admin title="My shift">
    <x-page-header :title="'Good '.(now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening')).', '.Str::before(auth()->user()->name, ' ')" subtitle="Your orders and the open queue." />

    <div class="row g-3 mb-4">
        <div class="col-sm-4"><x-stat-card label="Assigned to me" :value="$mine->count()" icon="person-check" /></div>
        <div class="col-sm-4"><x-stat-card label="Unassigned queue" :value="$queue->count()" icon="inboxes" /></div>
        <div class="col-sm-4"><x-stat-card label="Completed today" :value="$doneToday" icon="check2-circle" /></div>
    </div>

    <div class="row g-3">
        @foreach (['My orders' => $mine, 'Open queue' => $queue] as $heading => $list)
            <div class="col-xl-6">
                <x-card :title="$heading" flush>
                    <x-table>
                        <x-slot:head><th>Order</th><th>Type</th><th>Status</th><th><span class="visually-hidden">Action</span></th></x-slot:head>
                        @forelse ($list as $order)
                            <tr>
                                <td data-label="Order"><a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold">{{ $order->number }}</a>
                                    <span class="d-block small text-secondary">{{ $order->items_count }} {{ Str::plural('item', $order->items_count) }} · {{ $order->placed_at->diffForHumans() }}</span></td>
                                <td data-label="Type">{{ $order->fulfillment_type->label() }}</td>
                                <td data-label="Status"><x-status-badge :status="$order->status" /></td>
                                <td data-label="" class="text-end">
                                    @if ($order->assigned_to)
                                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-primary">Open</a>
                                    @else
                                        <form method="POST" action="{{ route('admin.orders.claim', $order) }}">@csrf<button class="btn btn-sm btn-outline-primary">Take it</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty-state icon="cup-hot" title="Nothing here right now" /></td></tr>
                        @endforelse
                    </x-table>
                </x-card>
            </div>
        @endforeach
    </div>

    @if ($lowStock->isNotEmpty())
        <x-card title="Running low" icon="exclamation-triangle" class="mt-3">
            <p class="small text-secondary">Let a manager know if you can't finish an order.</p>
            <div class="d-flex flex-wrap gap-2">@foreach ($lowStock as $item)<x-badge tone="warning">{{ $item->name }}: {{ $item->formatQuantity() }}</x-badge>@endforeach</div>
        </x-card>
    @endif
</x-layouts.admin>
