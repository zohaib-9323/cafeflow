@php
    use App\Enums\OrderStatus;
    use App\Support\Money;
    $u = auth()->user();
    $canUpdate = $u->can('updateStatus', $order);
    $forward = array_values(array_filter($next, fn ($s) => $s !== OrderStatus::Cancelled));
@endphp
<x-layouts.admin :title="'Order '.$order->number">
    <x-page-header :title="'Order '.$order->number" :breadcrumbs="['Orders' => route('admin.orders.index'), $order->number]"
                   :subtitle="'Placed '.$order->placed_at->format('D, M j Y · g:i A').' ('.$order->placed_at->diffForHumans().')'">
        <a href="{{ route('admin.orders.receipt', $order) }}" class="btn btn-light" target="_blank" rel="noopener"><i class="bi bi-printer me-1" aria-hidden="true"></i>Receipt</a>
    </x-page-header>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card flush>
                <x-slot:actions>
                    <x-status-badge :status="$order->status" class="fs-6" />
                </x-slot:actions>
                <div class="card-body border-bottom">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @if ($canUpdate && $forward)
                            @foreach ($forward as $status)
                                <form method="POST" action="{{ route('admin.orders.status', $order) }}" data-status-form>
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $status->value }}">
                                    <button class="btn btn-primary"><i class="bi bi-arrow-right-circle me-1" aria-hidden="true"></i>Mark {{ $status->label() }}</button>
                                </form>
                            @endforeach
                        @elseif (! $order->status->isActive())
                            <span class="text-secondary">This order is {{ strtolower($order->status->label()) }}. No further actions.</span>
                        @elseif (! $canUpdate)
                            @can('claim', $order)
                                <form method="POST" action="{{ route('admin.orders.claim', $order) }}">@csrf<button class="btn btn-primary"><i class="bi bi-hand-index me-1" aria-hidden="true"></i>Take this order</button></form>
                            @else
                                <span class="text-secondary">Assigned to {{ $order->assignee?->name }}.</span>
                            @endcan
                        @endif
                        @can('cancel', $order)
                            <button class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancel order</button>
                        @endcan
                    </div>
                    @if ($order->status === OrderStatus::Pending && $order->payment_method->value === 'card' && $order->payment_status->value !== 'paid')
                        <p class="small text-warning-emphasis mt-2 mb-0"><i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>Waiting for online payment before it can be confirmed.</p>
                    @endif
                </div>

                <x-table :stack="false">
                    <x-slot:head><th>Item</th><th class="text-center">Qty</th><th class="text-end">Unit</th><th class="text-end">Total</th></x-slot:head>
                    @foreach ($order->items as $item)
                        <tr>
                            <td><span class="fw-medium">{{ $item->product_name }}</span>
                                <span class="d-block small text-secondary">{{ collect([$item->variation_name, ...$item->addons->map(fn ($a) => $a->name.' (+'.Money::format($a->price).')')])->filter()->implode(' · ') }}</span></td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">{{ Money::format($item->unit_price) }}</td>
                            <td class="text-end">{{ Money::format($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </x-table>
                <div class="card-body">
                    <dl class="row mb-0 small offset-md-6">
                        <dt class="col-7 fw-normal text-secondary">Subtotal</dt><dd class="col-5 text-end mb-1">{{ Money::format($order->subtotal) }}</dd>
                        @if ((float) $order->discount_total)<dt class="col-7 fw-normal text-success">Discount @if ($order->promotion)({{ $order->promotion->code }})@endif</dt><dd class="col-5 text-end mb-1 text-success">-{{ Money::format($order->discount_total) }}</dd>@endif
                        <dt class="col-7 fw-normal text-secondary">Tax</dt><dd class="col-5 text-end mb-1">{{ Money::format($order->tax_total) }}</dd>
                        @if ($order->fulfillment_type->value === 'delivery')<dt class="col-7 fw-normal text-secondary">Delivery</dt><dd class="col-5 text-end mb-1">{{ (float) $order->delivery_fee ? Money::format($order->delivery_fee) : 'Free' }}</dd>@endif
                        <dt class="col-7 fs-6">Total</dt><dd class="col-5 text-end fs-6 fw-semibold mb-0">{{ Money::format($order->total) }}</dd>
                    </dl>
                </div>
            </x-card>

            <div class="row g-3 mt-0">
                <div class="col-md-6">
                    <x-card title="Timeline" icon="clock-history">
                        <ol class="list-unstyled mb-0 small">
                            @foreach ($order->statusHistory as $h)
                                <li class="d-flex gap-2 pb-2">
                                    <i class="bi bi-circle-fill text-coffee" style="font-size:.5rem;margin-top:.45rem" aria-hidden="true"></i>
                                    <div><strong>{{ $h->to_status->label() }}</strong> · {{ $h->created_at->format('M j, g:i A') }}
                                        <div class="text-secondary">{{ $h->changedBy?->name ?? 'System' }}@if ($h->note) · “{{ $h->note }}”@endif</div></div>
                                </li>
                            @endforeach
                        </ol>
                    </x-card>
                </div>
                <div class="col-md-6">
                    <x-card title="Stock movements" icon="box-seam">
                        @if ($order->inventoryMovements->isEmpty())
                            <p class="small text-secondary mb-0">Ingredients are deducted when the order is confirmed.</p>
                        @else
                            <ul class="list-unstyled small mb-0">
                                @foreach ($order->inventoryMovements as $m)
                                    <li class="d-flex justify-content-between py-1 border-bottom">
                                        <span>{{ $m->inventoryItem->name }} <span class="text-secondary">({{ $m->type->label() }})</span></span>
                                        <span class="{{ (float) $m->quantity_change < 0 ? 'text-danger' : 'text-success' }}">{{ (float) $m->quantity_change > 0 ? '+' : '' }}{{ $m->inventoryItem->formatQuantity($m->quantity_change) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-card>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <x-card title="Customer" icon="person" class="mb-3">
                <p class="mb-1 fw-semibold">{{ $order->customer_name }}</p>
                <p class="mb-1 small"><a href="mailto:{{ $order->customer_email }}">{{ $order->customer_email }}</a></p>
                <p class="mb-2 small"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $order->customer_phone) }}">{{ $order->customer_phone }}</a></p>
                <p class="small text-secondary mb-2">{{ $customerOrderCount }} {{ Str::plural('order', $customerOrderCount) }} with us</p>
                @can('manage-customers')<a href="{{ route('admin.customers.show', $order->user_id) }}" class="btn btn-sm btn-light">Customer profile</a>@endcan
            </x-card>

            <x-card :title="$order->fulfillment_type->label()" :icon="$order->fulfillment_type->value === 'pickup' ? 'shop' : 'bicycle'" class="mb-3">
                @if ($order->deliveryAddress())
                    <p class="mb-1">{{ $order->deliveryAddress() }}</p>
                    @if ($order->delivery_instructions)<p class="small text-secondary mb-0">{{ $order->delivery_instructions }}</p>@endif
                @else
                    <p class="mb-0 small text-secondary">Customer collects at the counter.</p>
                @endif
                @if ($order->notes)<hr><p class="small mb-0"><strong>Notes:</strong> {{ $order->notes }}</p>@endif
            </x-card>

            <x-card title="Payment" icon="credit-card" class="mb-3">
                <p class="mb-2">{{ $order->payment_method->label() }} · <x-badge :tone="$order->payment_status->tone()">{{ $order->payment_status->label() }}</x-badge></p>
                <ul class="list-unstyled small mb-0">
                    @foreach ($order->payments as $p)
                        <li class="border-top py-2">
                            <div class="d-flex justify-content-between"><span class="text-uppercase fw-semibold">{{ $p->provider }}</span><span>{{ Money::format($p->amount) }}</span></div>
                            <div class="text-secondary">{{ ucfirst($p->status->value) }}@if ($p->paid_at) · {{ $p->paid_at->format('M j, g:i A') }}@endif @if ($p->failure_reason) · {{ $p->failure_reason }}@endif</div>
                            @if ($p->provider_reference)<code class="small">{{ $p->provider_reference }}</code>@endif
                        </li>
                    @endforeach
                </ul>
            </x-card>

            <x-card title="Assignment" icon="person-badge">
                @can('assign', $order)
                    <form method="POST" action="{{ route('admin.orders.assign', $order) }}" class="d-flex gap-2">
                        @csrf
                        <label for="assigned_to" class="visually-hidden">Assign to</label>
                        <select id="assigned_to" name="assigned_to" class="form-select form-select-sm" @disabled(! $order->status->isActive())>
                            <option value="">Unassigned</option>
                            @foreach ($team as $member)<option value="{{ $member->id }}" @selected($order->assigned_to === $member->id)>{{ $member->name }}</option>@endforeach
                        </select>
                        <button class="btn btn-sm btn-light" @disabled(! $order->status->isActive())>Save</button>
                    </form>
                @else
                    <p class="mb-0 small">{{ $order->assignee?->name ?? 'Unassigned' }}</p>
                @endcan
            </x-card>
        </div>
    </div>

    @can('cancel', $order)
        <x-modal id="cancelModal" title="Cancel order {{ $order->number }}">
            <form method="POST" action="{{ route('admin.orders.status', $order) }}" data-status-form id="cancelForm">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="cancelled">
                <p class="small text-secondary">The customer will be emailed.
                    @if ($order->inventory_deducted_at) Ingredients already taken from stock will be returned. @endif
                    @if ($order->payment_status->value === 'paid') The online payment will be refunded. @endif</p>
                <x-textarea name="note" label="Reason (shown to the customer)" rows="2" required maxlength="500" wrapperClass="mb-0" />
            </form>
            <x-slot:footer>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep order</button>
                <button type="submit" form="cancelForm" class="btn btn-danger">Cancel order</button>
            </x-slot:footer>
        </x-modal>
    @endcan
</x-layouts.admin>
