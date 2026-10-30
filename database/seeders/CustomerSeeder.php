<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Fictional regulars and occasional visitors with Riverton addresses. */
class CustomerSeeder extends Seeder
{
    public const COUNT = 180;

    public function run(): void
    {
        $faker = fake('en_US');
        $faker->seed(2026); // same people on every fresh seed
        $roleId = Role::idFor(RoleEnum::Customer);
        $password = Hash::make(config('cafeflow.demo_password'));
        $streets = ['Mill Lane', 'Orchard Row', 'Foundry Yard', 'Canal Street', 'Linden Avenue', 'Weaver Close', 'Station Road', 'Elm Terrace', 'Harbour Walk', 'Chapel Street'];

        for ($i = 0; $i < self::COUNT; $i++) {
            $first = $faker->firstName();
            $last = $faker->lastName();
            $user = User::query()->create([
                'name' => "{$first} {$last}",
                'email' => Str::slug($first, '').'.'.Str::slug($last, '').$i.'@example.com',
                'password' => $password,
                'role_id' => $roleId,
                'is_active' => $i !== 17, // one deactivated account for the admin filter
            ]);
            $user->forceFill(['created_at' => now()->subDays($faker->numberBetween(20, 200))])->save();

            $profile = $user->customer()->create([
                'phone' => sprintf('(555) 0%02d-%04d', $faker->numberBetween(10, 19), $faker->numberBetween(1000, 9999)),
                'marketing_opt_in' => $faker->boolean(40),
            ]);
            if ($faker->boolean(70)) {
                $profile->addresses()->create([
                    'label' => 'Home',
                    'line1' => $faker->numberBetween(1, 240).' '.$faker->randomElement($streets),
                    'city' => 'Riverton',
                    'postal_code' => (string) $faker->numberBetween(10010, 10022),
                    'is_default' => true,
                ]);
            }
        }
    }
}
