<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PromotionType;
use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(Request $request): View
    {
        $promotions = Promotion::query()
            ->withCount('usages')
            ->withSum('usages as discount_given', 'discount_amount')
            ->latest()
            ->paginate(20);

        return view('admin.promotions.index', ['promotions' => $promotions]);
    }

    public function create(): View
    {
        return view('admin.promotions.form', ['promotion' => new Promotion(['type' => PromotionType::Percentage, 'is_active' => true, 'starts_at' => now()])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $promotion = Promotion::query()->create($this->validated($request));

        return redirect()->route('admin.promotions.index')->with('success', "Promo code {$promotion->code} created.");
    }

    public function edit(Promotion $promotion): View
    {
        return view('admin.promotions.form', ['promotion' => $promotion->loadCount('usages')]);
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $promotion->update($this->validated($request, $promotion));

        return redirect()->route('admin.promotions.index')->with('success', 'Promotion saved.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->update(['is_active' => false]);
        $promotion->delete(); // soft delete keeps past orders' references intact

        return redirect()->route('admin.promotions.index')->with('success', "{$promotion->code} deleted.");
    }

    public static function rules(?Promotion $promotion = null): array
    {
        return [
            'code' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('promotions', 'code')->ignore($promotion)],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::enum(PromotionType::class)],
            'value' => ['required', 'decimal:0,2', 'min:0.01', Rule::when(fn ($i) => ($i['type'] ?? null) === 'percentage', ['max:100'], ['max:9999.99'])],
            'min_order_amount' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999.99'],
            'max_discount' => ['nullable', 'decimal:0,2', 'min:0.01', 'max:9999.99'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }

    private function validated(Request $request, ?Promotion $promotion = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate(self::rules($promotion));

        return $data + ['is_active' => $request->boolean('is_active'), 'show_on_site' => $request->boolean('show_on_site')];
    }
}
