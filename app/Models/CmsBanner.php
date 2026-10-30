<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CmsBanner extends Model
{
    protected $fillable = ['title', 'subtitle', 'image_path', 'cta_label', 'cta_url', 'placement', 'sort_order', 'is_active', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function scopeLive(Builder $query, string $placement): void
    {
        $query->where('placement', $placement)->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('sort_order');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
