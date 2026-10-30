<?php

namespace Database\Seeders;

use App\Enums\PromotionType;
use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            ['WELCOME10', 'Welcome offer', '10% off your first order with us.', PromotionType::Percentage, 10, null, 8.00, null, 1, now()->subMonths(6), null, true, true],
            ['MORNING2', 'Weekday mornings', '$2 off any order over $12.', PromotionType::Fixed, 2, 12.00, null, null, null, now()->subMonths(2), null, true, false],
            ['BREW15', 'Brew bar launch', '15% off orders over $20 while the new single origin lasts.', PromotionType::Percentage, 15, 20.00, 10.00, 300, 2, now()->subDays(10), now()->addDays(20), true, true],
            ['SUMMER5', 'Summer terrace', '$5 off orders over $30.', PromotionType::Fixed, 5, 30.00, null, 200, null, now()->subMonths(4), now()->subMonths(1), true, false],
            ['STAFFTEST', 'Paused test code', 'Internal testing only.', PromotionType::Percentage, 50, null, null, 5, 1, null, null, false, false],
        ];

        foreach ($codes as [$code, $name, $desc, $type, $value, $min, $max, $limit, $perCustomer, $starts, $expires, $active, $onSite]) {
            Promotion::query()->updateOrCreate(['code' => $code], [
                'name' => $name, 'description' => $desc, 'type' => $type, 'value' => $value,
                'min_order_amount' => $min, 'max_discount' => $max, 'usage_limit' => $limit,
                'per_customer_limit' => $perCustomer, 'starts_at' => $starts, 'expires_at' => $expires,
                'is_active' => $active, 'show_on_site' => $onSite,
            ]);
        }
    }
}
