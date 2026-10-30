<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariation extends Model
{
    protected $fillable = ['name', 'price_adjustment', 'recipe_multiplier', 'is_default', 'sort_order'];

    protected function casts(): array
    {
        return ['price_adjustment' => 'decimal:2', 'recipe_multiplier' => 'decimal:3', 'is_default' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
