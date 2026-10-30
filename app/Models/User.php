<?php

namespace App\Models;

use App\Enums\Role as RoleEnum;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'role_id', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $with = ['role'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function assignedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'assigned_to');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function roleEnum(): RoleEnum
    {
        return $this->role->slug;
    }

    public function hasRole(RoleEnum ...$roles): bool
    {
        return in_array($this->roleEnum(), $roles, true);
    }

    public function isBackOffice(): bool
    {
        return $this->roleEnum()->isBackOffice();
    }

    public function scopeWithRole(Builder $query, RoleEnum ...$roles): void
    {
        $query->whereHas('role', fn (Builder $q) => $q->whereIn('slug', array_map(fn (RoleEnum $r) => $r->value, $roles)));
    }

    public function homeRoute(): string
    {
        return $this->isBackOffice() ? route('admin.dashboard') : route('account.orders.index');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
