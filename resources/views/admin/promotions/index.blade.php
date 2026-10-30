@php use App\Support\Money; @endphp
<x-layouts.admin title="Promotions">
    <x-page-header title="Promotions" :breadcrumbs="['Promotions']" subtitle="Promo codes customers enter at checkout.">
        <a href="{{ route('admin.promotions.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New promo code</a>
    </x-page-header>
    <div class="card">
        <x-table>
            <x-slot:head><th>Code</th><th>Offer</th><th>Valid</th><th class="text-end">Used</th><th class="text-end">Discount given</th><th>Status</th><th></th></x-slot:head>
            @forelse ($promotions as $p)
                @php
                    $state = ! $p->is_active ? ['Paused', 'secondary']
                        : ($p->expires_at?->isPast() ? ['Expired', 'dark']
                        : ($p->starts_at?->isFuture() ? ['Scheduled', 'info']
                        : ($p->usage_limit && $p->usages_count >= $p->usage_limit ? ['Used up', 'warning'] : ['Live', 'success'])));
                @endphp
                <tr>
                    <td data-label="Code"><code class="fs-6">{{ $p->code }}</code>@if ($p->show_on_site) <i class="bi bi-megaphone text-coffee" title="Advertised on site" aria-label="Advertised on site"></i>@endif<span class="d-block small text-secondary">{{ $p->name }}</span></td>
                    <td data-label="Offer">{{ $p->summary() }}@if ($p->max_discount)<span class="d-block small text-secondary">max {{ Money::format($p->max_discount) }}</span>@endif</td>
                    <td data-label="Valid" class="small">{{ $p->starts_at?->format('M j') ?? 'Any time' }} – {{ $p->expires_at?->format('M j, Y') ?? 'no end' }}</td>
                    <td data-label="Used" class="text-end">{{ $p->usages_count }}{{ $p->usage_limit ? ' / '.$p->usage_limit : '' }}</td>
                    <td data-label="Discount given" class="text-end">{{ Money::format($p->discount_given ?? 0) }}</td>
                    <td data-label="Status"><x-badge :tone="$state[1]">{{ $state[0] }}</x-badge></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.promotions.edit', $p) }}" class="btn btn-sm btn-light">Edit</a>
                        <x-confirm :action="route('admin.promotions.destroy', $p)" method="DELETE" class="d-inline" :message="'Delete code '.$p->code.'? Past orders keep their discount.'" confirm-label="Delete">
                            <button class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $p->code }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </x-confirm>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty-state icon="ticket-perforated" title="No promotions yet" /></td></tr>
            @endforelse
        </x-table>
        @if ($promotions->hasPages())<div class="card-footer bg-transparent"><x-pagination :paginator="$promotions" /></div>@endif
    </div>
</x-layouts.admin>
