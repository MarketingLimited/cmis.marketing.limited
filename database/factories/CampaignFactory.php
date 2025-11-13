<?php

namespace Database\Factories;

use App\Models\{Campaign, Org};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-3 months', '+1 month');
        $endDate = fake()->dateTimeBetween($startDate, '+6 months');

        return [
            'campaign_id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'campaign_name' => fake()->catchPhrase(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['draft', 'active', 'paused', 'completed']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'budget' => fake()->randomFloat(2, 1000, 100000),
            'objectives' => [fake()->word(), fake()->word()],
            'target_audience' => fake()->sentence(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_date' => now()->subDays(7),
            'end_date' => now()->addDays(30),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'start_date' => now()->subMonths(3),
            'end_date' => now()->subDays(1),
        ]);
    }
}
