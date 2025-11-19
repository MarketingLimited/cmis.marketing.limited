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
            'market_id' => (string) Str::uuid(),
            'code' => fake()->unique()->regexify('[A-Z]{2}'),
            'name' => fake()->country(),
            'locale' => fake()->randomElement(['ar-BH', 'en-US', 'ar-SA', 'en-GB']),
            'currency' => fake()->randomElement(['BHD', 'SAR', 'USD', 'GBP']),
            'timezone' => fake()->timezone(),
            'is_active' => true,
        ];
    }
}
