<?php

namespace App\Http\Controllers\Site;

use App\Enums\FulfillmentType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Cart\CartLine;
use App\Services\Cart\CartService;
use App\Services\Cart\PricingService;
use App\Services\Cart\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart, private readonly PricingService $pricing) {}

    public function index(Request $request): View
    {
        return view('site.cart', ['quote' => $this->currentQuote($request)]);
    }

    public function summary(Request $request): JsonResponse
    {
        return $this->json($request, 'Cart');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'variation_id' => ['nullable', 'integer'],
            'addon_ids' => ['sometimes', 'array', 'max:10'],
            'addon_ids.*' => ['integer', 'distinct'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config('cafeflow.max_item_quantity')],
        ]);

        $product = Product::query()->with(['variations', 'addons'])->find($data['product_id']);
        if (! $product || ! $product->isOrderable()) {
            return $this->fail($request, 'Sorry, that item is not available right now.');
        }
        if ($data['variation_id'] ?? null) {
            if (! $product->variations->contains('id', $data['variation_id'])) {
                return $this->fail($request, 'Please choose a valid size.');
            }
        }
        $activeAddons = $product->addons->where('is_active', true)->pluck('id');
        if (collect($data['addon_ids'] ?? [])->diff($activeAddons)->isNotEmpty()) {
            return $this->fail($request, 'One of the selected extras is not available.');
        }

        $this->cart->add(CartLine::make($product->id, $data['variation_id'] ?? null, $data['addon_ids'] ?? [], $data['quantity']));

        return $this->respond($request, "{$product->name} added to your cart.");
    }

    public function update(Request $request, string $key): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:'.config('cafeflow.max_item_quantity')]]);
        $this->cart->update($key, $data['quantity']);

        return $this->respond($request, 'Cart updated.');
    }

    public function destroy(Request $request, string $key): JsonResponse|RedirectResponse
    {
        $this->cart->remove($key);

        return $this->respond($request, 'Item removed.');
    }

    public function promo(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['promo_code' => ['nullable', 'string', 'max:40', 'alpha_dash']]);
        $this->cart->setPromoCode($data['promo_code'] ?? null);

        $quote = $this->currentQuote($request);
        if ($quote->promoError) {
            $this->cart->setPromoCode(null);

            return $this->fail($request, $quote->promoError, 'promo_code');
        }

        return $this->respond($request, $quote->promotion ? "Code {$quote->promotion->code} applied." : 'Promo code removed.');
    }

    public function fulfillment(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['fulfillment_type' => ['required', Rule::enum(FulfillmentType::class)]]);
        $this->cart->setFulfillment($data['fulfillment_type']);

        return $this->respond($request, 'Updated.');
    }

    private function currentQuote(Request $request): Quote
    {
        $quote = $this->pricing->quote(
            $this->cart->lines(),
            FulfillmentType::from($this->cart->fulfillment()),
            $this->cart->promoCode(),
            $request->user(),
        );

        // Drop lines that can no longer be priced; the quote's issues explain why, once.
        foreach ($quote->invalidKeys as $key) {
            $this->cart->remove($key);
        }

        return $quote;
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson() ? $this->json($request, $message) : back()->with('success', $message);
    }

    private function json(Request $request, string $message): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $this->currentQuote($request)->toArray()]);
    }

    private function fail(Request $request, string $message, string $field = 'cart'): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => $message, 'errors' => [$field => [$message]]], 422)
            : back()->withErrors([$field => $message]);
    }
}
