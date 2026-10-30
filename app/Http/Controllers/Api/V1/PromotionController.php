<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Admin\PromotionController as AdminPromotions;
use App\Http\Resources\PromotionResource;
use App\Models\Promotion;
use App\Services\PromotionService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->ok(PromotionResource::collection(Promotion::query()->withCount('usages')->latest()->paginate($this->perPage())));
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate(AdminPromotions::rules());
        $promotion = Promotion::query()->create($data + ['is_active' => $request->boolean('is_active', true)]);

        return $this->ok(new PromotionResource($promotion), 'Promotion created', 201);
    }

    /** Check a code against a subtotal without placing an order. */
    public function validateCode(Request $request, PromotionService $promotions): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'subtotal' => ['required', 'decimal:0,2', 'min:0', 'max:99999.99'],
        ]);

        [$promotion, $discount] = $promotions->apply($data['code'], Money::toCents($data['subtotal']), $request->user());

        return $this->ok([
            'code' => $promotion->code,
            'name' => $promotion->name,
            'discount' => Money::fromCents($discount),
        ], 'Promo code is valid');
    }
}
