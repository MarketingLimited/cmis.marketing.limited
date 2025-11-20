<?php

namespace Database\Factories\Audit;

use App\Models\Audit\ActivityLog;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Audit\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions = ['created', 'updated', 'deleted', 'viewed', 'exported'];
        $entityTypes = ['campaign', 'content', 'integration', 'user', 'asset'];

        return [
            'log_id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement($actions),
            'entity_type' => fake()->randomElement($entityTypes),
            'entity_id' => (string) Str::uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'metadata' => [
                'changes' => [],
                'context' => 'web',
            ],
        ];
    }
}
