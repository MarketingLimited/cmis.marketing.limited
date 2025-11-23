<?php

namespace Database\Factories\Analytics;

use App\Models\Analytics\Experiment;
use App\Models\Analytics\ExperimentVariant;
use App\Models\Analytics\ExperimentEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExperimentEventFactory extends Factory
{
    protected $model = ExperimentEvent::class;

    public function definition(): array
    {
        return [
            'experiment_id' => Experiment::factory(),
            'variant_id' => ExperimentVariant::factory(),
            'event_type' => $this->faker->randomElement(['impression', 'click', 'conversion']),
            'user_id' => 'user-' . $this->faker->uuid(),
            'session_id' => 'session-' . $this->faker->uuid(),
            'value' => null,
            'properties' => null,
            'occurred_at' => now(),
        ];
    }

    public function impression(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'impression',
            'value' => null,
        ]);
    }

    public function click(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'click',
            'value' => $this->faker->randomFloat(2, 0.5, 5.0),
        ]);
    }

    public function conversion(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'conversion',
            'value' => $this->faker->randomFloat(2, 10, 500),
        ]);
    }
}
