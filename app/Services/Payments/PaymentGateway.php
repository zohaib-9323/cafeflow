<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /** Identifier stored in payments.provider. */
    public function name(): string;

    /**
     * Start a hosted checkout for the payment. Returns the provider reference
     * and the URL to send the customer to. Card details never touch CafeFlow.
     *
     * @return array{reference: string, redirect_url: string}
     */
    public function createCheckout(Payment $payment, Order $order): array;

    /**
     * Verify the webhook signature and normalise the event.
     *
     * @throws InvalidWebhookSignature
     */
    public function parseWebhook(Request $request): WebhookEvent;

    public function refund(Payment $payment): void;
}
