<?php

namespace Database\Factories\Content;

use App\Models\Content\ContentPlan;
use App\Models\Core\Org;
use App\Models\Campaign\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContentPlanFactory extends Factory
{
    protected $model = ContentPlan::class;

    public function definition(): array
    {
        return [
            'plan_id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'campaign_id' => Campaign::factory(),
            'name' => fake()->words(3, true),
            'strategy' => [
                'goals' => fake()->words(5),
                'tactics' => fake()->words(5),
            ],
            'timeframe_daterange' => null,
            'brief_id' => null,
            'creative_context_id' => null,
        ];
    }

    public function withoutCampaign(): static
    {
        return $this->state(fn (array $attributes) => [
            'campaign_id' => null,
        ]);
    }
}
