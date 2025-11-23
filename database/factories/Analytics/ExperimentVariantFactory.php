<?php

namespace Database\Factories\Analytics;

use App\Models\Analytics\Experiment;
use App\Models\Analytics\ExperimentVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExperimentVariantFactory extends Factory
{
    protected $model = ExperimentVariant::class;

    public function definition(): array
    {
        return [
            'experiment_id' => Experiment::factory(),
            'name' => $this->faker->randomElement(['Control', 'Variant A', 'Variant B', 'Variant C']),
            'description' => $this->faker->sentence(),
            'is_control' => false,
            'traffic_percentage' => 50.00,
            'config' => [
                'creative_id' => $this->faker->uuid(),
                'copy' => $this->faker->sentence(),
            ],
            'impressions' => 0,
            'clicks' => 0,
            'conversions' => 0,
            'spend' => 0,
            'revenue' => 0,
            'metrics' => null,
            'conversion_rate' => 0,
            'improvement_over_control' => null,
            'confidence_interval_lower' => null,
            'confidence_interval_upper' => null,
            'status' => 'active',
        ];
    }

    public function control(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Control',
            'is_control' => true,
        ]);
    }

    public function withPerformance(): static
    {
        $impressions = $this->faker->numberBetween(1000, 10000);
        $clicks = $this->faker->numberBetween(100, $impressions / 5);
        $conversions = $this->faker->numberBetween(10, $clicks / 5);
        $spend = $this->faker->randomFloat(2, 100, 1000);

        return $this->state(fn (array $attributes) => [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'spend' => $spend,
            'revenue' => $conversions * $this->faker->randomFloat(2, 10, 100),
            'conversion_rate' => ($conversions / $impressions) * 100,
        ]);
    }
}
