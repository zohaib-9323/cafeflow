<?php

namespace App\Models;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['slug', 'name'];

    protected function casts(): array
    {
        return ['slug' => RoleEnum::class];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public static function idFor(RoleEnum $role): int
    {
        return static::query()->where('slug', $role->value)->valueOrFail('id');
    }
}
