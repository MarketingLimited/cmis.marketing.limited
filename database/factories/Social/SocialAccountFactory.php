<?php

namespace Database\Factories\Social;

use App\Models\Social\SocialAccount;
use App\Models\Core\Org;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'integration_id' => Integration::factory(),
            'account_external_id' => fake()->uuid(),
            'username' => fake()->userName(),
            'display_name' => fake()->company(),
            'profile_picture_url' => fake()->imageUrl(200, 200),
            'biography' => fake()->paragraph(),
            'followers_count' => fake()->numberBetween(100, 100000),
            'follows_count' => fake()->numberBetween(50, 5000),
            'media_count' => fake()->numberBetween(10, 1000),
            'website' => fake()->url(),
            'category' => fake()->randomElement(['Business', 'Personal', 'Creator', 'Brand']),
            'fetched_at' => now(),
            'provider' => fake()->randomElement(['facebook', 'instagram', 'twitter', 'linkedin']),
        ];
    }

    public function instagram(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'instagram',
            'category' => 'Business',
        ]);
    }

    public function facebook(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'facebook',
            'category' => 'Brand',
        ]);
    }

    public function twitter(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'twitter',
            'follows_count' => fake()->numberBetween(100, 2000),
        ]);
    }

    public function linkedin(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'linkedin',
            'category' => 'Business',
        ]);
    }

    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'followers_count' => fake()->numberBetween(50000, 1000000),
            'media_count' => fake()->numberBetween(500, 5000),
        ]);
    }
}
