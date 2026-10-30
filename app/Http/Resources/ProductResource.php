<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->when($request->user()?->can('view-catalog'), $this->sku),
            'short_description' => $this->short_description,
            'description' => $this->description,
            'price' => $this->price,
            'status' => $this->when($request->user()?->can('view-catalog'), $this->status?->value),
            'is_available' => $this->is_available,
            'is_featured' => $this->is_featured,
            'prep_minutes' => $this->prep_minutes,
            'calories' => $this->calories,
            'image_url' => $this->imageUrl(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($i) => ['url' => $i->url(), 'alt' => $i->alt])),
            'variations' => $this->whenLoaded('variations', fn () => $this->variations->map(fn ($v) => [
                'id' => $v->id, 'name' => $v->name, 'price_adjustment' => $v->price_adjustment, 'is_default' => $v->is_default,
            ])),
            'addons' => $this->whenLoaded('addons', fn () => $this->addons->where('is_active', true)->values()->map(fn ($a) => [
                'id' => $a->id, 'name' => $a->name, 'price' => $a->price,
            ])),
        ];
    }
}
