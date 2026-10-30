<?php

namespace App\Models;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    // Status, money and timestamps are set by OrderService, never mass-assigned from input.
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'fulfillment_type' => FulfillmentType::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'placed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'inventory_deducted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class)->withTrashed();
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function scopeRevenue(Builder $query): void
    {
        $query->whereIn('status', OrderStatus::revenueStatuses());
    }

    public function scopePlacedBetween(Builder $query, $from, $to): void
    {
        $query->whereBetween('placed_at', [$from, $to]);
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }
        $query->where(fn (Builder $q) => $q
            ->where('number', 'like', "%{$term}%")
            ->orWhere('customer_name', 'like', "%{$term}%")
            ->orWhere('customer_email', 'like', "%{$term}%")
            ->orWhere('customer_phone', 'like', "%{$term}%"));
    }

    public function deliveryAddress(): ?string
    {
        if ($this->fulfillment_type !== FulfillmentType::Delivery) {
            return null;
        }

        return collect([$this->delivery_line1, $this->delivery_line2, $this->delivery_city, $this->delivery_postal_code])
            ->filter()->implode(', ');
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }
}
