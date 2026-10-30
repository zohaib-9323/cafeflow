<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionUsage extends Model
{
    protected $fillable = ['promotion_id', 'order_id', 'user_id', 'discount_amount'];

    protected function casts(): array
    {
        return ['discount_amount' => 'decimal:2'];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class)->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
