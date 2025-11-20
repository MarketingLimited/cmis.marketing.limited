<?php

namespace Database\Factories\Social;

use App\Models\Social\ScheduledSocialPost;
use App\Models\Social\SocialPost;
use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ScheduledSocialPostFactory extends Factory
{
    protected $model = ScheduledSocialPost::class;

    public function definition(): array
    {
        $statuses = ['scheduled', 'published', 'failed', 'cancelled'];

        return [
            'scheduled_post_id' => (string) Str::uuid(),
            'social_post_id' => SocialPost::factory(),
            'org_id' => Org::factory(),
            'scheduled_at' => fake()->dateTimeBetween('now', '+7 days'),
            'status' => fake()->randomElement($statuses),
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => fake()->dateTimeBetween('+1 hour', '+7 days'),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'scheduled_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
