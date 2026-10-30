<?php

namespace Database\Factories;

use App\Enums\PromotionType;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Promotion> */
class PromotionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'TEST'.Str::upper(Str::random(6)),
            'name' => 'Test promotion',
            'type' => PromotionType::Percentage,
            'value' => '10.00',
            'is_active' => true,
            'show_on_site' => false,
        ];
    }

    public function fixed(string $amount): static
    {
        return $this->state(fn () => ['type' => PromotionType::Fixed, 'value' => $amount]);
    }
}
