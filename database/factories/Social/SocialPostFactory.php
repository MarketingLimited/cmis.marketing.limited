<?php

namespace Database\Factories\Social;

use App\Models\Social\SocialPost;
use App\Models\Core\Org;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SocialPostFactory extends Factory
{
    protected $model = SocialPost::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'integration_id' => Integration::factory(),
            'post_external_id' => fake()->uuid(),
            'caption' => fake()->paragraph(),
            'media_url' => fake()->imageUrl(800, 600),
            'permalink' => fake()->url(),
            'media_type' => fake()->randomElement(['IMAGE', 'VIDEO', 'CAROUSEL_ALBUM']),
            'posted_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'metrics' => [
                'likes' => fake()->numberBetween(0, 1000),
                'comments' => fake()->numberBetween(0, 100),
                'shares' => fake()->numberBetween(0, 50),
                'impressions' => fake()->numberBetween(100, 10000),
                'reach' => fake()->numberBetween(50, 5000),
            ],
            'fetched_at' => now(),
            'provider' => fake()->randomElement(['facebook', 'instagram', 'twitter', 'linkedin']),
        ];
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'media_type' => 'IMAGE',
            'media_url' => fake()->imageUrl(1200, 630),
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'media_type' => 'VIDEO',
            'video_url' => 'https://example.com/video.mp4',
            'thumbnail_url' => fake()->imageUrl(640, 360),
        ]);
    }

    public function carousel(): static
    {
        return $this->state(fn (array $attributes) => [
            'media_type' => 'CAROUSEL_ALBUM',
            'children_media' => [
                ['type' => 'IMAGE', 'url' => fake()->imageUrl()],
                ['type' => 'IMAGE', 'url' => fake()->imageUrl()],
                ['type' => 'IMAGE', 'url' => fake()->imageUrl()],
            ],
        ]);
    }
}
