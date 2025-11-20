<?php

namespace Database\Factories\Market;

use App\Models\Market\Market;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MarketFactory extends Factory
{
    protected $model = Market::class;

    public function definition(): array
    {
        return [
            'market_id' => fake()->numberBetween(1, 999),
            'market_name' => fake()->country(),
            'language_code' => fake()->randomElement(['ar-BH', 'en-US', 'ar-SA', 'en-GB']),
            'currency_code' => fake()->randomElement(['BHD', 'SAR', 'USD', 'GBP']),
            'text_direction' => fake()->randomElement(['RTL', 'LTR']),
        ];
    }
}
