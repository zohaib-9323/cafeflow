<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['inventory_item_id', 'type', 'quantity_change', 'quantity_after', 'order_id', 'user_id', 'reason'];

    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'quantity_change' => 'decimal:3',
            'quantity_after' => 'decimal:3',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class)->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
