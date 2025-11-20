<?php

namespace Database\Factories\AdPlatform;

use App\Models\AdPlatform\AdMetric;
use App\Models\Core\Org;
use App\Models\Core\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdPlatform\AdMetric>
 */
class AdMetricFactory extends Factory
{
    protected $model = AdMetric::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        $entityLevels = ['campaign', 'adset', 'ad'];

        $impressions = fake()->numberBetween(1000, 100000);
        $clicks = fake()->numberBetween(10, (int)($impressions * 0.05));
        $spend = fake()->randomFloat(2, 10, 1000);
        $conversions = fake()->numberBetween(0, (int)($clicks * 0.1));

        return [
            'id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'integration_id' => Integration::factory(),
            'entity_level' => fake()->randomElement($entityLevels),
            'entity_external_id' => 'entity_' . fake()->numerify('##########'),
            'date_start' => fake()->dateTimeBetween('-30 days', 'now'),
            'date_stop' => fake()->dateTimeBetween('now', '+1 day'),
            'spend' => $spend,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'actions' => json_encode([
                'link_click' => $clicks,
                'post_engagement' => fake()->numberBetween(0, $clicks),
            ]),
            'conversions' => $conversions,
            'provider' => fake()->randomElement($providers),
        ];
    }

    /**
     * Set the provider.
     */
    public function forProvider(string $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
        ]);
    }

    /**
     * Set the entity level.
     */
    public function forEntityLevel(string $level): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_level' => $level,
        ]);
    }
}
