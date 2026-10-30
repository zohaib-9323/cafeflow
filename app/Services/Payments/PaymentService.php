<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Notifications\PaymentReceived;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentService
{
    /** @param array<string, PaymentGateway> $gateways */
    public function __construct(private readonly array $gateways) {}

    public function gateway(?string $name = null): PaymentGateway
    {
        $name ??= config('cafeflow.payments.gateway');
        if ($name === 'mock' && app()->isProduction()) {
            throw new InvalidArgumentException('The mock payment gateway is disabled in production.');
        }

        return $this->gateways[$name] ?? throw new InvalidArgumentException("Unknown payment gateway [{$name}]");
    }

    /**
     * Start (or retry) online payment for an order and return the redirect URL.
     * Each attempt is its own payments row with a unique idempotency key; earlier
     * attempts that never complete simply stay pending and are ignored.
     */
    public function startCheckout(Order $order): string
    {
        if ($order->payment_method !== PaymentMethod::Card) {
            throw new BusinessRuleException('This order is not paid online.');
        }
        if ($order->payment_status === PaymentStatus::Paid) {
            throw new BusinessRuleException('This order is already paid.');
        }
        if ($order->status === OrderStatus::Cancelled) {
            throw new BusinessRuleException('This order was cancelled.');
        }

        $gateway = $this->gateway();

        $payment = DB::transaction(function () use ($order, $gateway) {
            Order::query()->whereKey($order->id)->lockForUpdate()->first();

            $attempt = $order->payments()->count() + 1;

            return $order->payments()->create([
                'provider' => $gateway->name(),
                'idempotency_key' => "{$order->number}-card-{$attempt}",
                'amount' => $order->total,
                'currency' => $order->currency,
                'status' => TransactionStatus::Pending,
            ]);
        });

        try {
            $checkout = $gateway->createCheckout($payment, $order);
        } catch (\Throwable $e) {
            $payment->update(['status' => TransactionStatus::Failed, 'failure_reason' => 'Could not start checkout']);
            Log::error('Payment checkout failed to start', ['order' => $order->number, 'error' => $e->getMessage()]);
            throw new BusinessRuleException('We could not reach the payment provider. Please try again in a moment.');
        }

        $payment->update(['provider_reference' => $checkout['reference']]);
        $order->update(['payment_status' => PaymentStatus::Pending]);

        return $checkout['redirect_url'];
    }

    /**
     * Process a webhook. Safe to receive the same event any number of times: the
     * event id is recorded under a unique index, and a payment only moves from
     * pending to a final state once.
     *
     * @return string "processed" | "duplicate" | "ignored"
     *
     * @throws InvalidWebhookSignature
     */
    public function handleWebhook(string $provider, Request $request): string
    {
        $event = $this->gateway($provider)->parseWebhook($request);

        $result = DB::transaction(function () use ($provider, $event) {
            try {
                $record = PaymentWebhookEvent::query()->create([
                    'provider' => $provider,
                    'event_id' => $event->id,
                    'type' => $event->type,
                    'processed_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                return 'duplicate';
            }

            if (! $event->outcome || ! $event->reference) {
                return 'ignored';
            }

            $payment = Payment::query()
                ->where('provider', $provider)
                ->where('provider_reference', $event->reference)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                Log::warning('Webhook for unknown payment reference', ['provider' => $provider, 'reference' => $event->reference]);

                return 'ignored';
            }

            $record->update(['payment_id' => $payment->id]);

            if ($payment->status->isFinal()) {
                return 'ignored'; // e.g. a late "failed" after we already recorded success
            }

            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);

            if ($event->outcome === WebhookEvent::SUCCEEDED) {
                $payment->update([
                    'status' => TransactionStatus::Succeeded,
                    'paid_at' => now(),
                    'meta' => array_merge($payment->meta ?? [], $event->meta),
                ]);
                $order->update(['payment_status' => PaymentStatus::Paid]);
                DB::afterCommit(fn () => $order->user?->notify(new PaymentReceived($order)));
            } else {
                $payment->update(['status' => TransactionStatus::Failed, 'failure_reason' => $event->failureReason]);
                if ($order->payment_status !== PaymentStatus::Paid) {
                    $order->update(['payment_status' => PaymentStatus::Failed]);
                }
            }

            return 'processed';
        });

        Log::info('Payment webhook', ['provider' => $provider, 'event' => $event->id, 'type' => $event->type, 'result' => $result]);

        return $result;
    }

    public function refund(Payment $payment): void
    {
        try {
            if ($payment->provider !== 'cash') {
                $this->gateway($payment->provider)->refund($payment);
            }
            DB::transaction(function () use ($payment) {
                $payment->update(['status' => TransactionStatus::Refunded]);
                $payment->order()->update(['payment_status' => PaymentStatus::Refunded]);
            });
        } catch (\Throwable $e) {
            // The cancellation stands; finance staff see a paid + cancelled order and refund by hand.
            Log::error('Refund failed', ['payment' => $payment->id, 'error' => $e->getMessage()]);
        }
    }
}
