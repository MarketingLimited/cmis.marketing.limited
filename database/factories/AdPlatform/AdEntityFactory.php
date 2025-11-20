<?php

namespace Database\Factories\AdPlatform;

use App\Models\AdPlatform\AdEntity;
use App\Models\AdPlatform\AdSet;
use App\Models\Core\Org;
use App\Models\Core\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdPlatform\AdEntity>
 */
class AdEntityFactory extends Factory
{
    protected $model = AdEntity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        $statuses = ['active', 'paused', 'archived', 'deleted'];

        return [
            'id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'integration_id' => Integration::factory(),
            'adset_external_id' => 'adset_' . fake()->numerify('##########'),
            'ad_external_id' => 'ad_' . fake()->numerify('##########'),
            'name' => fake()->sentence(3),
            'status' => fake()->randomElement($statuses),
            'creative_id' => (string) Str::uuid(),
            'provider' => fake()->randomElement($providers),
            'deleted_by' => null,
        ];
    }

    /**
     * Indicate that the entity is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Set the entity provider.
     */
    public function forProvider(string $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
        ]);
    }
}
