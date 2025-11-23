<?php

namespace Database\Factories\Analytics;

use App\Models\Analytics\AlertHistory;
use App\Models\Analytics\AlertNotification;
use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertNotificationFactory extends Factory
{
    protected $model = AlertNotification::class;

    public function definition(): array
    {
        return [
            'alert_id' => AlertHistory::factory(),
            'org_id' => Org::factory(),
            'channel' => fake()->randomElement(['email', 'in_app', 'slack', 'webhook']),
            'recipient' => fake()->email(),
            'sent_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'status' => 'sent',
            'error_message' => null,
            'retry_count' => 0,
            'delivered_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'read_at' => null,
            'metadata' => [],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'delivered_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => fake()->sentence(),
            'retry_count' => fake()->numberBetween(1, 3),
            'delivered_at' => null,
        ]);
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'email',
            'recipient' => fake()->email(),
        ]);
    }

    public function slack(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'slack',
            'recipient' => '#' . fake()->word(),
        ]);
    }
}
