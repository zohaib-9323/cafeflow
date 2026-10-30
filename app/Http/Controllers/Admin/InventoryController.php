<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InventoryUnit;
use App\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'stock' => ['nullable', Rule::in(['low', 'ok'])],
            'supplier' => ['nullable', 'integer'],
        ]);

        $items = InventoryItem::query()
            ->with('supplier:id,name')
            ->withCount('recipes')
            ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($q) => $q->where('name', 'like', "%{$t}%")->orWhere('sku', 'like', "%{$t}%")))
            ->when(($filters['stock'] ?? null) === 'low', fn ($q) => $q->lowStock())
            ->when(($filters['stock'] ?? null) === 'ok', fn ($q) => $q->whereColumn('quantity', '>', 'low_stock_threshold'))
            ->when($filters['supplier'] ?? null, fn ($q, $s) => $q->where('supplier_id', $s))
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        return view('admin.inventory.index', [
            'items' => $items,
            'filters' => $filters,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'lowCount' => InventoryItem::query()->lowStock()->count(),
            'stockValue' => (float) InventoryItem::query()->selectRaw('SUM(GREATEST(quantity, 0) * COALESCE(cost_per_unit, 0)) as v')->value('v'),
            'movementTypes' => $request->user()->can('manage-inventory') ? MovementType::manual() : [MovementType::Waste],
        ]);
    }

    public function show(Request $request, InventoryItem $item): View
    {
        return view('admin.inventory.show', [
            'item' => $item->load('supplier'),
            'movements' => $item->movements()->with(['user:id,name', 'order:id,number'])->paginate(25),
            'usedIn' => $item->recipes()->with('product:id,name,slug')->get(),
            'movementTypes' => $request->user()->can('manage-inventory') ? MovementType::manual() : [MovementType::Waste],
        ]);
    }

    public function create(): View
    {
        return $this->form(new InventoryItem(['unit' => InventoryUnit::Gram]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $item = InventoryItem::query()->create(Arr::except($data, 'opening_quantity'));

        if ((float) ($data['opening_quantity'] ?? 0) > 0) {
            $this->inventory->adjust($item, MovementType::Restock, (string) $data['opening_quantity'], 'Opening stock', $request->user());
        }

        return redirect()->route('admin.inventory.show', $item)->with('success', "{$item->name} added.");
    }

    public function edit(InventoryItem $item): View
    {
        return $this->form($item);
    }

    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        // Quantity is deliberately not editable here: use a stock adjustment so the change is logged.
        $item->update($this->validated($request, $item));

        return redirect()->route('admin.inventory.show', $item)->with('success', 'Ingredient saved.');
    }

    public function adjust(Request $request, InventoryItem $item): RedirectResponse|JsonResponse
    {
        $allowed = $request->user()->can('manage-inventory') ? MovementType::manual() : [MovementType::Waste];
        $data = $request->validate([
            'type' => ['required', Rule::in(array_map(fn ($t) => $t->value, $allowed))],
            'quantity' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'reason' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('type') !== 'restock')],
        ], ['reason.required' => 'Please note why stock is being removed or corrected.']);

        $movement = $this->inventory->adjust($item, MovementType::from($data['type']), (string) $data['quantity'], $data['reason'] ?? null, $request->user());
        $item->refresh();
        $message = "{$item->name}: {$movement->type->label()} recorded. Now {$item->formatQuantity()}.";

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $message, 'data' => ['quantity' => $item->formatQuantity(), 'is_low' => $item->isLow()]])
            : back()->with('success', $message);
    }

    private function form(InventoryItem $item): View
    {
        return view('admin.inventory.form', [
            'item' => $item,
            'units' => InventoryUnit::cases(),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function validated(Request $request, ?InventoryItem $item = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('inventory_items', 'sku')->ignore($item)],
            'unit' => ['required', Rule::enum(InventoryUnit::class)],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'low_stock_threshold' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'cost_per_unit' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'opening_quantity' => [$item ? 'prohibited' : 'nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);
        $data['allow_negative'] = $request->boolean('allow_negative');

        return $data;
    }
}
