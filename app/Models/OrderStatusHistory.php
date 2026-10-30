<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['from_status' => OrderStatus::class, 'to_status' => OrderStatus::class];
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by')->withTrashed();
    }
}
