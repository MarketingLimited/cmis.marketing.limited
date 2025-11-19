<?php

namespace Database\Factories\Core;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrgFactory extends Factory
{
    protected $model = Org::class;

    public function definition(): array
    {
        return [
            'org_id' => (string) Str::uuid(),
            'name' => fake()->company(),
            'default_locale' => fake()->randomElement(['ar-BH', 'en-US', 'ar-SA']),
            'currency' => fake()->randomElement(['BHD', 'SAR', 'USD']),
            'provider' => fake()->randomElement(['meta', 'google', 'internal']),
        ];
    }
}
