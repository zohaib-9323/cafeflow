<?php

namespace App\Models;

use App\Enums\InventoryUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_id', 'name', 'sku', 'unit', 'low_stock_threshold', 'cost_per_unit', 'allow_negative',
    ];

    protected function casts(): array
    {
        return [
            'unit' => InventoryUnit::class,
            'quantity' => 'decimal:3',
            'low_stock_threshold' => 'decimal:3',
            'cost_per_unit' => 'decimal:4',
            'allow_negative' => 'boolean',
            'low_stock_notified_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest('created_at')->latest('id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class);
    }

    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('quantity', '<=', 'low_stock_threshold');
    }

    public function isLow(): bool
    {
        return (float) $this->quantity <= (float) $this->low_stock_threshold;
    }

    public function formatQuantity(string|float|null $value = null): string
    {
        $value = (float) ($value ?? $this->quantity);

        return rtrim(rtrim(number_format($value, 3, '.', ','), '0'), '.').' '.$this->unit->value;
    }
}
