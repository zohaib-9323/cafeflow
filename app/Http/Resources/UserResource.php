<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->roleEnum()->value,
            'phone' => $this->whenLoaded('customer', fn () => $this->customer?->phone),
            'is_active' => $this->is_active,
            'orders_count' => $this->whenCounted('orders'),
            'total_spent' => $this->when(isset($this->total_spent), fn () => number_format((float) $this->total_spent, 2, '.', '')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
