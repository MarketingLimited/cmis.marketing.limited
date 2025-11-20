<?php

namespace Database\Factories\Social;

use App\Models\Social\SocialPost;
use App\Models\Core\Org;
use App\Models\Core\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SocialPostFactory extends Factory
{
    protected $model = SocialPost::class;

    public function definition(): array
    {
        $platforms = ['meta', 'twitter', 'linkedin', 'instagram', 'tiktok'];
        $statuses = ['draft', 'scheduled', 'published', 'failed'];

        return [
            'post_id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'integration_id' => Integration::factory(),
            'platform' => fake()->randomElement($platforms),
            'post_external_id' => fake()->numerify('post_##########'),
            'content' => fake()->paragraph(),
            'status' => fake()->randomElement($statuses),
            'scheduled_for' => fake()->optional()->dateTimeBetween('now', '+7 days'),
            'published_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'engagement_count' => fake()->numberBetween(0, 10000),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }
}
