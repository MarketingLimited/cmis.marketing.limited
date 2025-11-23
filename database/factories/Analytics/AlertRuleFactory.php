<?php

namespace Database\Factories\Analytics;

use App\Models\Analytics\AlertRule;
use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertRuleFactory extends Factory
{
    protected $model = AlertRule::class;

    public function definition(): array
    {
        return [
            'org_id' => Org::factory(),
            'created_by' => User::factory(),
            'name' => fake()->words(3, true) . ' Alert',
            'description' => fake()->sentence(),
            'entity_type' => fake()->randomElement(['campaign', 'ad', 'post', 'organization']),
            'entity_id' => fake()->optional()->uuid(),
            'metric' => fake()->randomElement(['ctr', 'roi', 'spend', 'impressions', 'clicks', 'conversions']),
            'condition' => fake()->randomElement(['gt', 'gte', 'lt', 'lte', 'eq', 'ne']),
            'threshold' => fake()->randomFloat(2, 1, 100),
            'time_window_minutes' => fake()->randomElement([15, 30, 60, 120, 240]),
            'severity' => fake()->randomElement(['critical', 'high', 'medium', 'low']),
            'notification_channels' => ['email', 'in_app'],
            'notification_config' => [
                'email' => [
                    'recipients' => [fake()->email()],
                ],
                'in_app' => [
                    'user_ids' => [],
                ],
            ],
            'cooldown_minutes' => fake()->randomElement([30, 60, 120, 240]),
            'is_active' => fake()->boolean(80), // 80% active
            'last_triggered_at' => null,
            'trigger_count' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
        ]);
    }

    public function triggered(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_triggered_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'trigger_count' => fake()->numberBetween(1, 10),
        ]);
    }
}
