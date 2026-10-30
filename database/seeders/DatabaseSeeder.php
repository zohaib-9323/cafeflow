<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Roles and starter site content are always seeded.
     * Demo accounts, the sample menu and order history are seeded only outside
     * production, so a live install never ships with known passwords.
     */
    public function run(): void
    {
        $this->call([RoleSeeder::class, ContentSeeder::class]);

        if (app()->isProduction()) {
            $this->command?->warn('Production: skipped demo users, sample menu and order history. Create an admin with `php artisan cafeflow:create-admin`.');

            return;
        }

        $this->call([
            DemoUserSeeder::class,
            CatalogSeeder::class,
            PromotionSeeder::class,
            CustomerSeeder::class,
            OrderHistorySeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
