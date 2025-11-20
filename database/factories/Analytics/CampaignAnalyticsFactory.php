<?php

namespace Database\Factories\Analytics;

use App\Models\Analytics\CampaignAnalytics;
use App\Models\Campaign;
use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Analytics\CampaignAnalytics>
 */
class CampaignAnalyticsFactory extends Factory
{
    protected $model = CampaignAnalytics::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'analytics_id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'campaign_id' => Campaign::factory(),
            'metric_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'impressions' => fake()->numberBetween(1000, 100000),
            'clicks' => fake()->numberBetween(10, 5000),
            'conversions' => fake()->numberBetween(0, 500),
            'spend' => fake()->randomFloat(2, 10, 10000),
            'revenue' => fake()->randomFloat(2, 0, 15000),
            'ctr' => fake()->randomFloat(4, 0.01, 0.15),
            'cpc' => fake()->randomFloat(4, 0.10, 5.00),
            'roas' => fake()->randomFloat(2, 0.5, 10.0),
        ];
    }
}
