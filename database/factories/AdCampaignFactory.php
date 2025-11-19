<?php

namespace Database\Factories;

use App\Models\AdCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AdCampaignFactory extends Factory
{
    protected $model = AdCampaign::class;

    public function definition(): array
    {
        return [
            'ad_campaign_id' => (string) Str::uuid(),
            'org_id' => (string) Str::uuid(),
            'name' => fake()->words(3, true),
            'status' => fake()->randomElement(['active', 'paused', 'completed']),
        ];
    }
}
