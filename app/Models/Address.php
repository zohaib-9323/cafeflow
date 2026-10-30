<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = ['label', 'line1', 'line2', 'city', 'postal_code', 'instructions', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function oneLine(): string
    {
        return collect([$this->line1, $this->line2, $this->city, $this->postal_code])->filter()->implode(', ');
    }
}
