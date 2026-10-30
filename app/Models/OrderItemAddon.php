<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemAddon extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(ProductAddon::class, 'product_addon_id');
    }
}
