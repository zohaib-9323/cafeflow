<?php

namespace App\Models;

use App\Enums\PromotionType;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'description', 'type', 'value', 'min_order_amount', 'max_discount',
        'usage_limit', 'per_customer_limit', 'starts_at', 'expires_at', 'is_active', 'show_on_site',
    ];

    protected function casts(): array
    {
        return [
            'type' => PromotionType::class,
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'show_on_site' => 'boolean',
        ];
    }

    protected function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    public function scopeCurrentlyValid(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function summary(): string
    {
        $value = $this->type === PromotionType::Percentage
            ? rtrim(rtrim($this->value, '0'), '.').'% off'
            : Money::format($this->value).' off';

        return $this->min_order_amount ? "{$value} orders over ".Money::format($this->min_order_amount) : $value;
    }
}
