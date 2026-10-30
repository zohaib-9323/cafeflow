<?php

namespace App\Http\Controllers\Payments;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where the customer lands after the hosted checkout. The query string is only a
 * hint for the message: the payment status itself comes from the verified webhook.
 */
class ReturnController extends Controller
{
    public function __invoke(Request $request, Payment $payment): RedirectResponse
    {
        $payment->load('order');
        abort_unless($payment->order->user_id === $request->user()->id, 404);
        $order = $payment->order;

        return match (true) {
            $payment->status === TransactionStatus::Succeeded => redirect()->route('account.orders.show', $order)
                ->with('success', "Payment received. Thank you! Order {$order->number} is in the queue."),
            $payment->status === TransactionStatus::Failed => redirect()->route('account.orders.show', $order)
                ->with('warning', 'Your payment did not go through. You can try again below.'),
            $request->query('result') === 'cancelled' => redirect()->route('account.orders.show', $order)
                ->with('warning', 'Payment was cancelled. Your order is saved; you can pay below.'),
            default => redirect()->route('account.orders.show', $order)
                ->with('info', "We're confirming your payment with the provider. This page will update shortly."),
        };
    }
}
