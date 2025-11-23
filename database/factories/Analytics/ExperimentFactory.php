<?php

namespace Database\Factories\Analytics;

use App\Models\Analytics\Experiment;
use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExperimentFactory extends Factory
{
    protected $model = Experiment::class;

    public function definition(): array
    {
        return [
            'org_id' => Org::factory(),
            'created_by' => User::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'experiment_type' => $this->faker->randomElement(['campaign', 'content', 'audience', 'budget']),
            'entity_type' => 'campaign',
            'entity_id' => $this->faker->uuid(),
            'metric' => 'conversion_rate',
            'metrics' => ['ctr', 'cpc', 'roi'],
            'hypothesis' => $this->faker->sentence(),
            'status' => 'draft',
            'start_date' => null,
            'end_date' => null,
            'duration_days' => 14,
            'sample_size_per_variant' => 1000,
            'confidence_level' => 95.00,
            'minimum_detectable_effect' => 5.00,
            'traffic_allocation' => 'equal',
            'config' => [],
            'started_at' => null,
            'completed_at' => null,
            'winner_variant_id' => null,
            'statistical_significance' => null,
            'results' => null,
        ];
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => now()->subDays(rand(1, 7)),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subDays(rand(14, 30)),
            'completed_at' => now()->subDays(rand(1, 5)),
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
            'started_at' => now()->subDays(rand(1, 7)),
        ]);
    }
}
