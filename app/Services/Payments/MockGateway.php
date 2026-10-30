<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * DEVELOPMENT ONLY: a local stand-in for a hosted card checkout.
 *
 * The customer lands on a CafeFlow page that is clearly labelled as a test gateway,
 * with "approve" and "decline" buttons and no card fields. Either button produces a
 * signed webhook that goes through exactly the same verification and idempotent
 * processing as a real provider's.
 */
class MockGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'mock';
    }

    public function createCheckout(Payment $payment, Order $order): array
    {
        return [
            'reference' => 'mock_'.Str::lower(Str::random(24)),
            'redirect_url' => URL::temporarySignedRoute('payments.mock.show', now()->addMinutes(30), ['payment' => $payment->id]),
        ];
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        $payload = $request->getContent();
        HmacSignature::verify($payload, $request->header('CafeFlow-Signature'), $this->secret());

        $event = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

        return new WebhookEvent(
            id: (string) $event['id'],
            type: (string) $event['type'],
            reference: $event['data']['reference'] ?? null,
            outcome: match ($event['type']) {
                'payment.succeeded' => WebhookEvent::SUCCEEDED,
                'payment.failed' => WebhookEvent::FAILED,
                default => null,
            },
            failureReason: $event['data']['failure_reason'] ?? null,
        );
    }

    /** Build the signed event the mock checkout page "receives" from the gateway. */
    public function simulateEvent(Payment $payment, bool $approve): array
    {
        $payload = json_encode([
            'id' => 'evt_mock_'.Str::lower(Str::random(20)),
            'type' => $approve ? 'payment.succeeded' : 'payment.failed',
            'data' => [
                'reference' => $payment->provider_reference,
                'amount' => $payment->amount,
                'failure_reason' => $approve ? null : 'Card declined (test)',
            ],
        ], JSON_THROW_ON_ERROR);

        return [$payload, HmacSignature::sign($payload, $this->secret())];
    }

    public function refund(Payment $payment): void
    {
        // Nothing to call: the mock gateway "refunds" instantly.
    }

    private function secret(): ?string
    {
        return config('cafeflow.payments.mock.webhook_secret');
    }
}
