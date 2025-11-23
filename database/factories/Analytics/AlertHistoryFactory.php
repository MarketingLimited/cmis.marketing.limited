<?php

namespace Database\Factories\Analytics;

use App\Models\Analytics\AlertHistory;
use App\Models\Analytics\AlertRule;
use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertHistoryFactory extends Factory
{
    protected $model = AlertHistory::class;

    public function definition(): array
    {
        $threshold = fake()->randomFloat(2, 1, 100);
        $actual = $threshold + fake()->randomFloat(2, 1, 50); // Actual > threshold

        return [
            'rule_id' => AlertRule::factory(),
            'org_id' => Org::factory(),
            'triggered_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'entity_type' => fake()->randomElement(['campaign', 'ad', 'post']),
            'entity_id' => fake()->uuid(),
            'metric' => fake()->randomElement(['ctr', 'roi', 'spend', 'impressions']),
            'actual_value' => $actual,
            'threshold_value' => $threshold,
            'condition' => fake()->randomElement(['gt', 'lt', 'gte', 'lte']),
            'severity' => fake()->randomElement(['critical', 'high', 'medium', 'low']),
            'message' => fake()->sentence(),
            'metadata' => [
                'rule_name' => fake()->words(3, true),
                'time_window' => 60,
                'evaluation_time' => now()->toIso8601String(),
            ],
            'status' => 'new',
            'acknowledged_by' => null,
            'acknowledged_at' => null,
            'resolved_at' => null,
            'snoozed_until' => null,
            'resolution_notes' => null,
        ];
    }

    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'acknowledged',
            'acknowledged_by' => User::factory(),
            'acknowledged_at' => fake()->dateTimeBetween('-3 days', 'now'),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'acknowledged_by' => User::factory(),
            'acknowledged_at' => fake()->dateTimeBetween('-5 days', '-2 days'),
            'resolved_at' => fake()->dateTimeBetween('-2 days', 'now'),
            'resolution_notes' => fake()->sentence(),
        ]);
    }

    public function snoozed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'snoozed',
            'snoozed_until' => fake()->dateTimeBetween('now', '+2 hours'),
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
        ]);
    }
}
