<?php

namespace Database\Factories;

use App\Enums\Role as RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role_id' => fn () => Role::query()->where('slug', RoleEnum::Customer)->value('id'),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** Requires the roles table to be seeded (see Tests\TestCase). */
    public function role(RoleEnum $role): static
    {
        return $this->state(fn () => ['role_id' => Role::query()->where('slug', $role)->value('id')]);
    }

    public function superAdmin(): static
    {
        return $this->role(RoleEnum::SuperAdmin);
    }

    public function manager(): static
    {
        return $this->role(RoleEnum::Manager);
    }

    public function staff(): static
    {
        return $this->role(RoleEnum::Staff);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
