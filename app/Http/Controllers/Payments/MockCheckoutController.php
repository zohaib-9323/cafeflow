<?php

namespace App\Http\Controllers\Payments;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\MockGateway;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/** The local test gateway's "hosted checkout" page. Development only. */
class MockCheckoutController extends Controller
{
    public function show(Request $request, Payment $payment): View
    {
        $this->guard($request, $payment);

        return view('payments.mock', [
            'payment' => $payment->load('order'),
            'action' => URL::temporarySignedRoute('payments.mock.complete', now()->addMinutes(30), ['payment' => $payment->id]),
        ]);
    }

    public function complete(Request $request, Payment $payment, MockGateway $gateway, PaymentService $payments): RedirectResponse
    {
        $this->guard($request, $payment);
        $approve = $request->input('outcome') === 'approve';

        // Deliver the event through the real webhook pipeline (signature check + idempotency).
        [$payload, $signature] = $gateway->simulateEvent($payment, $approve);
        $webhook = Request::create('/webhooks/payments/mock', 'POST', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CAFEFLOW_SIGNATURE' => $signature,
        ], content: $payload);
        $payments->handleWebhook('mock', $webhook);

        return redirect()->route('payments.return', ['payment' => $payment->id, 'result' => $approve ? 'success' : 'failed']);
    }

    private function guard(Request $request, Payment $payment): void
    {
        abort_if(app()->isProduction(), 404); // the test gateway never runs in production
        abort_unless($payment->provider === 'mock' && $payment->order->user_id === $request->user()->id, 404);
        abort_if($payment->status !== TransactionStatus::Pending, 410, 'This payment link has already been used.');
    }
}
