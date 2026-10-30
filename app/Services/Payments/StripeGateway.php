<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Stripe Checkout (test mode) over Stripe's REST API, using the Laravel HTTP client
 * instead of the SDK. Set STRIPE_SECRET (sk_test_...) and STRIPE_WEBHOOK_SECRET (whsec_...).
 */
class StripeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'stripe';
    }

    public function createCheckout(Payment $payment, Order $order): array
    {
        $response = $this->client()
            ->withHeaders(['Idempotency-Key' => $payment->idempotency_key])
            ->asForm()
            ->post('/checkout/sessions', [
                'mode' => 'payment',
                'client_reference_id' => $order->number,
                'customer_email' => $order->customer_email,
                'success_url' => route('payments.return', ['payment' => $payment->id, 'result' => 'success']),
                'cancel_url' => route('payments.return', ['payment' => $payment->id, 'result' => 'cancelled']),
                'metadata' => ['payment_id' => $payment->id, 'order_number' => $order->number],
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($order->currency),
                        'unit_amount' => Money::toCents($order->total),
                        'product_data' => ['name' => "CafeFlow order {$order->number}"],
                    ],
                ]],
            ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('Stripe rejected the checkout request: '.$response->json('error.message', 'unknown error'));
        }

        return ['reference' => $response->json('id'), 'redirect_url' => $response->json('url')];
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        $payload = $request->getContent();
        HmacSignature::verify(
            $payload,
            $request->header('Stripe-Signature'),
            config('cafeflow.payments.stripe.webhook_secret'),
            (int) config('cafeflow.payments.stripe.tolerance_seconds'),
        );

        $event = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        $object = $event['data']['object'] ?? [];

        $outcome = match ($event['type']) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => ($object['payment_status'] ?? null) === 'paid' ? WebhookEvent::SUCCEEDED : null,
            'checkout.session.async_payment_failed', 'checkout.session.expired' => WebhookEvent::FAILED,
            default => null,
        };

        return new WebhookEvent(
            id: (string) $event['id'],
            type: (string) $event['type'],
            reference: $object['id'] ?? null,
            outcome: $outcome,
            failureReason: $outcome === WebhookEvent::FAILED ? str_replace('checkout.session.', 'Checkout ', $event['type']) : null,
            meta: array_filter(['payment_intent' => $object['payment_intent'] ?? null]),
        );
    }

    public function refund(Payment $payment): void
    {
        $intent = $payment->meta['payment_intent'] ?? null;
        if (! $intent) {
            throw new PaymentGatewayException("Payment {$payment->id} has no payment_intent to refund.");
        }

        $response = $this->client()
            ->withHeaders(['Idempotency-Key' => "refund-{$payment->idempotency_key}"])
            ->asForm()
            ->post('/refunds', ['payment_intent' => $intent]);

        if ($response->failed()) {
            throw new PaymentGatewayException('Stripe refund failed: '.$response->json('error.message', 'unknown error'));
        }
    }

    private function client(): PendingRequest
    {
        $secret = config('cafeflow.payments.stripe.secret');
        if (! $secret) {
            throw new PaymentGatewayException('Stripe is selected but STRIPE_SECRET is not set.');
        }

        return Http::baseUrl(config('cafeflow.payments.stripe.api_base'))
            ->withToken($secret)
            ->timeout(15)
            ->retry(2, 300, fn ($e) => $e instanceof ConnectionException, throw: false);
    }
}
