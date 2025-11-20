<?php

namespace Database\Factories\AdPlatform;

use App\Models\AdPlatform\AdSet;
use App\Models\Core\Org;
use App\Models\Core\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdPlatform\AdSet>
 */
class AdSetFactory extends Factory
{
    protected $model = AdSet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        $statuses = ['active', 'paused', 'archived'];
        $billingEvents = ['impressions', 'clicks', 'conversions'];
        $optimizationGoals = ['reach', 'impressions', 'clicks', 'conversions', 'engagement'];

        return [
            'id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'integration_id' => Integration::factory(),
            'campaign_external_id' => 'campaign_' . fake()->numerify('##########'),
            'adset_external_id' => 'adset_' . fake()->numerify('##########'),
            'name' => fake()->sentence(3),
            'status' => fake()->randomElement($statuses),
            'daily_budget' => fake()->randomFloat(2, 10, 1000),
            'start_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'end_date' => fake()->dateTimeBetween('now', '+60 days'),
            'billing_event' => fake()->randomElement($billingEvents),
            'optimization_goal' => fake()->randomElement($optimizationGoals),
            'provider' => fake()->randomElement($providers),
            'deleted_by' => null,
        ];
    }

    /**
     * Indicate that the ad set is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }
}
