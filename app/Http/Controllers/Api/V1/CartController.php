<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\FulfillmentType;
use App\Services\Cart\CartLine;
use App\Services\Cart\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Stateless pricing: send line items, get server-calculated totals back. */
class CartController extends ApiController
{
    public function quote(Request $request, PricingService $pricing): JsonResponse
    {
        $data = self::validateLines($request);
        $quote = $pricing->quote(
            array_map(fn ($l) => CartLine::fromArray($l), $data['items']),
            FulfillmentType::from($data['fulfillment_type']),
            $data['promo_code'] ?? null,
            $request->user('sanctum'),
        );

        return $this->ok($quote->toArray(), $quote->canCheckout() ? 'Quote calculated' : 'Quote calculated with issues');
    }

    public static function validateLines(Request $request): array
    {
        return $request->validate([
            'fulfillment_type' => ['required', Rule::enum(FulfillmentType::class)],
            'promo_code' => ['nullable', 'string', 'max:40', 'alpha_dash'],
            'items' => ['required', 'array', 'min:1', 'max:'.config('cafeflow.max_cart_lines')],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variation_id' => ['nullable', 'integer'],
            'items.*.addon_ids' => ['sometimes', 'array', 'max:10'],
            'items.*.addon_ids.*' => ['integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.config('cafeflow.max_item_quantity')],
        ]);
    }
}
