{{-- Order totals. Values come from the server-side Quote; JS only refreshes them from the server. --}}
@php use App\Support\Money; @endphp
<dl class="mb-0">
    <div class="d-flex justify-content-between py-1"><dt class="fw-normal text-secondary">Subtotal</dt><dd class="mb-0" data-summary="subtotal">{{ Money::format($quote->subtotalCents) }}</dd></div>
    <div @class(['d-flex justify-content-between py-1 text-success', 'd-none' => ! $quote->discountCents]) data-summary-row="discount_total">
        <dt class="fw-normal">Discount @if ($quote->promotion)<span class="badge text-bg-light border font-monospace">{{ $quote->promotion->code }}</span>@endif</dt>
        <dd class="mb-0" data-summary="discount_total">-{{ Money::format($quote->discountCents) }}</dd>
    </div>
    <div class="d-flex justify-content-between py-1"><dt class="fw-normal text-secondary">Tax ({{ rtrim(rtrim(number_format(config('cafeflow.tax_rate') * 100, 2), '0'), '.') }}%)</dt><dd class="mb-0" data-summary="tax_total">{{ Money::format($quote->taxCents) }}</dd></div>
    <div @class(['d-flex justify-content-between py-1', 'd-none' => $quote->fulfillment->value !== 'delivery']) data-summary-row="delivery_fee">
        <dt class="fw-normal text-secondary">Delivery</dt><dd class="mb-0" data-summary="delivery_fee">{{ Money::format($quote->deliveryCents) }}</dd>
    </div>
    <div class="d-flex justify-content-between pt-3 mt-2 border-top fs-5 fw-semibold"><dt>Total</dt><dd class="mb-0" data-summary="total">{{ Money::format($quote->totalCents) }}</dd></div>
</dl>
