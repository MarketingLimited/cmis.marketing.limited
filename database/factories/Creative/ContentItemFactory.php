<?php

namespace Database\Factories\Creative;

use App\Models\Creative\ContentItem;
use App\Models\Core\Org;
use App\Models\Creative\ContentPlan;
use App\Models\CreativeAsset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContentItemFactory extends Factory
{
    protected $model = ContentItem::class;

    public function definition(): array
    {
        $statuses = ['draft', 'scheduled', 'published', 'archived'];

        return [
            'item_id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'plan_id' => ContentPlan::factory(),
            'channel_id' => fake()->numberBetween(1, 10),
            'format_id' => fake()->numberBetween(1, 5),
            'title' => fake()->sentence(),
            'brief' => [
                'objective' => fake()->sentence(),
                'target_audience' => fake()->words(3, true),
                'key_message' => fake()->paragraph(),
                'tone' => fake()->randomElement(['professional', 'casual', 'formal', 'friendly']),
            ],
            'status' => fake()->randomElement($statuses),
            'scheduled_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'asset_id' => null,
            'context_id' => null,
            'example_id' => null,
            'creative_context_id' => null,
            'provider' => fake()->randomElement(['internal', 'external', 'ai_generated']),
        ];
    }

    public function withAsset(): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_id' => CreativeAsset::factory(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+30 days'),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }
}
