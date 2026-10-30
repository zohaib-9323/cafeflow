<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/** The four documented demo accounts. Never runs in production. */
class DemoUserSeeder extends Seeder
{
    public const ACCOUNTS = [
        ['Avery Lin', 'admin@cafeflow.demo', RoleEnum::SuperAdmin],
        ['Marcus Bell', 'manager@cafeflow.demo', RoleEnum::Manager],
        ['Priya Nair', 'staff@cafeflow.demo', RoleEnum::Staff],
        ['Jordan Reyes', 'customer@cafeflow.demo', RoleEnum::Customer],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo accounts must not be created in production.');
        }

        $password = Hash::make(config('cafeflow.demo_password'));
        foreach (self::ACCOUNTS as [$name, $email, $role]) {
            $user = User::query()->updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => $password,
                'role_id' => Role::idFor($role),
                'is_active' => true,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        // A second barista so orders can be split between people.
        User::query()->updateOrCreate(['email' => 'sam.okafor@cafeflow.demo'], [
            'name' => 'Sam Okafor', 'password' => $password, 'role_id' => Role::idFor(RoleEnum::Staff), 'is_active' => true,
        ]);

        $jordan = User::query()->where('email', 'customer@cafeflow.demo')->first();
        $profile = $jordan->customer()->firstOrCreate([], ['phone' => '(555) 014-7731', 'marketing_opt_in' => true]);
        $profile->addresses()->firstOrCreate(['label' => 'Home'], [
            'line1' => '18 Orchard Row', 'line2' => 'Flat 3', 'city' => 'Riverton', 'postal_code' => '10016',
            'instructions' => 'Side door, ring twice.', 'is_default' => true,
        ]);
        $profile->addresses()->firstOrCreate(['label' => 'Studio'], [
            'line1' => '7 Foundry Yard', 'city' => 'Riverton', 'postal_code' => '10011', 'is_default' => false,
        ]);
    }
}
