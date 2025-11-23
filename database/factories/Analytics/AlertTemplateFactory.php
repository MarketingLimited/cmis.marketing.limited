<?php

namespace Database\Factories\Analytics;

use App\Models\Analytics\AlertTemplate;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertTemplateFactory extends Factory
{
    protected $model = AlertTemplate::class;

    public function definition(): array
    {
        return [
            'created_by' => fake()->optional()->passthrough(User::factory()),
            'name' => fake()->words(3, true) . ' Template',
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['performance', 'budget', 'engagement', 'anomaly']),
            'entity_type' => fake()->randomElement(['campaign', 'ad', 'post']),
            'default_config' => [
                'metric' => fake()->randomElement(['ctr', 'roi', 'spend']),
                'condition' => 'gt',
                'threshold' => 5.0,
                'severity' => 'medium',
                'notification_channels' => ['email'],
                'notification_config' => [
                    'email' => ['recipients' => []],
                ],
                'cooldown_minutes' => 60,
            ],
            'is_public' => false,
            'is_system' => false,
            'usage_count' => 0,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
            'is_public' => true,
            'created_by' => null,
        ]);
    }

    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_count' => fake()->numberBetween(10, 100),
        ]);
    }
}
