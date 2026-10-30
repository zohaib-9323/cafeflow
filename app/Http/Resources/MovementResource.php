<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'quantity_change' => $this->quantity_change,
            'quantity_after' => $this->quantity_after,
            'reason' => $this->reason,
            'order_number' => $this->whenLoaded('order', fn () => $this->order?->number),
            'by' => $this->whenLoaded('user', fn () => $this->user?->name),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
