@php use App\Support\Money; @endphp
<x-layouts.site title="Test payment">
    <div class="container py-5">
        <div class="auth-card mx-auto surface p-4 p-lg-5">
            <x-alert type="warning" :dismissible="false" icon="cone-striped">
                <strong>Development test gateway.</strong> This simulates a hosted card checkout. No card is entered and no money moves.
                Choose an outcome to send a signed webhook through CafeFlow's real payment pipeline.
            </x-alert>
            <h1 class="h3 mb-1">Pay {{ Money::format($payment->amount) }}</h1>
            <p class="text-secondary mb-4">Order {{ $payment->order->number }} · reference <code>{{ $payment->provider_reference }}</code></p>
            <form method="POST" action="{{ $action }}" class="d-grid gap-2" data-once>
                @csrf
                <button name="outcome" value="approve" class="btn btn-success btn-lg"><i class="bi bi-check-circle me-1" aria-hidden="true"></i> Simulate successful payment</button>
                <button name="outcome" value="decline" class="btn btn-outline-danger"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> Simulate declined card</button>
            </form>
            <p class="small text-secondary mt-3 mb-0">Switch to Stripe test mode with <code>PAYMENT_GATEWAY=stripe</code> (see README).</p>
        </div>
    </div>
</x-layouts.site>
