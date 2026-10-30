<?php

namespace App\Http\Controllers\Account;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.orders.index', [
            'orders' => $request->user()->orders()->withCount('items')->latest('placed_at')->paginate(10),
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);
        $order->load(['items.addons', 'statusHistory', 'latestPayment']);

        return view('account.orders.show', [
            'order' => $order,
            'steps' => OrderStatus::trackingSteps($order->fulfillment_type),
        ]);
    }

    /** Polled by the tracking page so customers see progress without reloading. */
    public function status(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json(['success' => true, 'data' => [
            'status' => $order->status->value,
            'label' => $order->status->label(),
            'payment_status' => $order->payment_status->value,
            'updated_at' => $order->updated_at->toIso8601String(),
        ]]);
    }

    public function cancel(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $this->authorize('cancelOwn', $order);
        $orders->cancelByCustomer($order, $request->user());

        return back()->with('success', "Order {$order->number} has been cancelled.");
    }

    public function pay(Order $order, PaymentService $payments): RedirectResponse
    {
        $this->authorize('pay', $order);

        return redirect()->away($payments->startCheckout($order));
    }
}
