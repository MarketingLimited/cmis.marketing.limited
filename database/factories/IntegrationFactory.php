<?php

namespace Database\Factories;

use App\Models\{Integration, Org};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    public function definition(): array
    {
        return [
            'integration_id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'platform' => fake()->randomElement(['instagram', 'facebook', 'meta_ads']),
            'account_id' => (string) fake()->numberBetween(100000000, 999999999),
            'access_token' => Str::random(64),
            'is_active' => true,
            'business_id' => (string) fake()->numberBetween(100000, 999999),
            'username' => fake()->userName(),
        ];
    }

    public function instagram(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'instagram',
        ]);
    }

    public function facebook(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'facebook',
        ]);
    }

    public function metaAds(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'meta_ads',
            'metadata' => ['ad_account_id' => fake()->numberBetween(100000, 999999)],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
