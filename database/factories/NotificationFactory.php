<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Core\User;
use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'notification_id' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'org_id' => Org::factory(),
            'type' => fake()->randomElement(['campaign', 'analytics', 'integration', 'user', 'creative', 'system', 'workflow', 'report']),
            'title' => fake()->sentence(3),
            'message' => fake()->sentence(10),
            'data' => json_encode([
                'action' => fake()->randomElement(['created', 'updated', 'deleted', 'completed']),
                'entity_id' => (string) Str::uuid(),
                'metadata' => ['key' => 'value']
            ]),
            'read' => false,
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function campaign(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'campaign',
            'title' => 'Campaign ' . fake()->randomElement(['Created', 'Updated', 'Completed']),
            'data' => json_encode([
                'campaign_id' => (string) Str::uuid(),
                'campaign_name' => fake()->words(3, true),
            ]),
        ]);
    }

    public function analytics(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'analytics',
            'title' => 'Analytics Report Available',
            'data' => json_encode([
                'report_type' => fake()->randomElement(['daily', 'weekly', 'monthly']),
                'date' => fake()->date(),
            ]),
        ]);
    }

    public function integration(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'integration',
            'title' => 'Integration ' . fake()->randomElement(['Connected', 'Disconnected', 'Error']),
            'data' => json_encode([
                'platform' => fake()->randomElement(['meta', 'google', 'tiktok', 'linkedin']),
                'status' => fake()->randomElement(['success', 'error', 'warning']),
            ]),
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'system',
            'title' => 'System Notification',
            'message' => fake()->sentence(),
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read' => true,
            'read_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read' => false,
            'read_at' => null,
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'updated_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
