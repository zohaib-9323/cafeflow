<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'number' => $this->number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'fulfillment_type' => $this->fulfillment_type->value,
            'payment_method' => $this->payment_method->value,
            'payment_status' => $this->payment_status->value,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'delivery_fee' => $this->delivery_fee,
            'total' => $this->total,
            'currency' => $this->currency,
            'customer' => [
                'name' => $this->customer_name,
                'email' => $this->customer_email,
                'phone' => $this->customer_phone,
            ],
            'delivery_address' => $this->deliveryAddress(),
            'notes' => $this->notes,
            'items_count' => $this->whenCounted('items'),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'name' => $i->product_name,
                'variation' => $i->variation_name,
                'addons' => $i->relationLoaded('addons') ? $i->addons->map(fn ($a) => ['name' => $a->name, 'price' => $a->price]) : [],
                'unit_price' => $i->unit_price,
                'quantity' => $i->quantity,
                'line_total' => $i->line_total,
            ])),
            'history' => $this->whenLoaded('statusHistory', fn () => $this->statusHistory->map(fn ($h) => [
                'status' => $h->to_status->value, 'at' => $h->created_at->toIso8601String(), 'note' => $h->note,
            ])),
            'placed_at' => $this->placed_at->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
        ];
    }
}
