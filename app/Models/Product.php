<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'short_description', 'description', 'price', 'status',
        'is_available', 'is_featured', 'prep_minutes', 'calories', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => ProductStatus::class,
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->ofMany(['sort_order' => 'min', 'id' => 'min']);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class)->orderBy('sort_order')->orderBy('id');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ProductAddon::class)->orderBy('sort_order')->orderBy('id');
    }

    public function recipe(): HasMany
    {
        return $this->hasMany(ProductRecipe::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Visible on the public menu (may still be marked sold out). */
    public function scopeListed(Builder $query): void
    {
        $query->where('status', ProductStatus::Active)
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true));
    }

    public function isOrderable(): bool
    {
        return $this->status === ProductStatus::Active && $this->is_available && ! $this->trashed();
    }

    public function imageUrl(): string
    {
        $path = $this->relationLoaded('primaryImage') ? $this->primaryImage?->path : $this->images()->value('path');

        return $path ? Storage::disk('public')->url($path) : asset('images/placeholder-product.svg');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
