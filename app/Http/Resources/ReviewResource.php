<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'title' => $this->title,
            'body' => $this->body,
            'author' => $this->whenLoaded('user', fn () => str($this->user->name)->before(' ')->toString()), // first name only
            'is_approved' => $this->is_approved,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
