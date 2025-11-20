<?php

namespace Database\Factories\Campaign;

use App\Models\Campaign\CampaignBudget;
use App\Models\Core\Org;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CampaignBudgetFactory extends Factory
{
    protected $model = CampaignBudget::class;

    public function definition(): array
    {
        return [
            'budget_id' => (string) Str::uuid(),
            'campaign_id' => Campaign::factory(),
            'org_id' => Org::factory(),
            'amount' => fake()->randomFloat(2, 10000, 1000000),
            'currency' => 'USD',
            'period' => fake()->randomElement(['daily', 'weekly', 'monthly', 'quarterly', 'lifetime']),
        ];
    }

    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'period' => 'daily',
            'amount' => fake()->randomFloat(2, 100, 5000),
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'period' => 'monthly',
            'amount' => fake()->randomFloat(2, 5000, 100000),
        ]);
    }

    public function lifetime(): static
    {
        return $this->state(fn (array $attributes) => [
            'period' => 'lifetime',
            'amount' => fake()->randomFloat(2, 50000, 1000000),
        ]);
    }
}
