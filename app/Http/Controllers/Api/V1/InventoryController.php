<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MovementType;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\MovementResource;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'low' => ['nullable', 'boolean']]);

        $items = InventoryItem::query()->with('supplier:id,name')
            ->when($filters['q'] ?? null, fn ($q, $t) => $q->where('name', 'like', "%{$t}%"))
            ->when($request->boolean('low'), fn ($q) => $q->lowStock())
            ->orderBy('name')->paginate($this->perPage())->withQueryString();

        return $this->ok(InventoryItemResource::collection($items));
    }

    public function movements(InventoryItem $item): JsonResponse
    {
        return $this->ok(MovementResource::collection($item->movements()->with(['user:id,name', 'order:id,number'])->paginate($this->perPage())));
    }

    public function adjust(Request $request, InventoryItem $item, InventoryService $inventory): JsonResponse
    {
        $allowed = $request->user()->can('manage-inventory') ? MovementType::manual() : [MovementType::Waste];
        $data = $request->validate([
            'type' => ['required', Rule::in(array_map(fn ($t) => $t->value, $allowed))],
            'quantity' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'reason' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('type') !== 'restock')],
        ]);

        $movement = $inventory->adjust($item, MovementType::from($data['type']), (string) $data['quantity'], $data['reason'] ?? null, $request->user());

        return $this->ok(new MovementResource($movement), 'Stock updated', 201);
    }
}
