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
            'org_name' => fake()->company(),
            'org_domain' => fake()->domainName(),
            'industry' => fake()->randomElement(['Technology', 'Healthcare', 'Finance', 'Retail', 'Education']),
            'logo_url' => fake()->imageUrl(200, 200, 'business'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
