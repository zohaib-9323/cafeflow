<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Testimonial extends Model
{
    protected $fillable = ['author_name', 'author_title', 'quote', 'rating', 'avatar_path', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'rating' => 'integer'];
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->orderBy('sort_order')->orderBy('id');
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }
}
