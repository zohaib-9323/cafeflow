<?php

namespace App\Http\Controllers\Site;

use App\Enums\FulfillmentType;
use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\Cart\CartService;
use App\Services\Cart\PricingService;
use App\Services\OrderService;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly PricingService $pricing,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user()->load('customer.addresses');
        $quote = $this->pricing->quote($this->cart->lines(), FulfillmentType::from($this->cart->fulfillment()), $this->cart->promoCode(), $user);

        if ($quote->isEmpty()) {
            return redirect()->route('menu.index')->with('warning', 'Your cart is empty. Add something tasty first.');
        }
        if (! $quote->canCheckout()) {
            return redirect()->route('cart.index');
        }

        return view('site.checkout', [
            'quote' => $quote,
            'user' => $user,
            'addresses' => $user->customer?->addresses ?? collect(),
            'gateway' => config('cafeflow.payments.gateway'),
        ]);
    }

    public function store(CheckoutRequest $request, OrderService $orders, PaymentService $payments): RedirectResponse
    {
        $data = $request->validated();
        $data['promo_code'] = $this->cart->promoCode();

        $order = $orders->place($request->user(), $this->cart->lines(), $data);
        $this->cart->clear();

        if ($request->boolean('save_address') && $order->fulfillment_type === FulfillmentType::Delivery) {
            $profile = $request->user()->customer()->firstOrCreate([]);
            $profile->addresses()->firstOrCreate(
                ['line1' => $order->delivery_line1, 'postal_code' => $order->delivery_postal_code],
                ['line2' => $order->delivery_line2, 'city' => $order->delivery_city, 'instructions' => $order->delivery_instructions,
                    'is_default' => $profile->addresses()->doesntExist()],
            );
        }

        if ($order->payment_method === PaymentMethod::Card) {
            try {
                return redirect()->away($payments->startCheckout($order));
            } catch (BusinessRuleException $e) {
                return redirect()->route('account.orders.show', $order)->with('warning', $e->getMessage().' Your order is saved, so you can retry payment below.');
            }
        }

        return redirect()->route('account.orders.show', $order)->with('success', "Thank you! Order {$order->number} has been placed.");
    }
}
