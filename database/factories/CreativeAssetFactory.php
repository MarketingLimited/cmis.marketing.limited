<?php

namespace Database\Factories;

use App\Models\CreativeAsset;
use App\Models\Core\Org;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CreativeAssetFactory extends Factory
{
    protected $model = CreativeAsset::class;

    public function definition(): array
    {
        return [
            'asset_id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'campaign_id' => Campaign::factory(),
            'strategy' => [
                'objective' => fake()->randomElement(['awareness', 'engagement', 'conversion']),
                'target_audience' => fake()->words(3, true),
            ],
            'channel_id' => fake()->numberBetween(1, 10),
            'format_id' => fake()->numberBetween(1, 20),
            'variation_tag' => fake()->word(),
            'copy_block' => fake()->paragraph(),
            'art_direction' => [
                'style' => fake()->randomElement(['modern', 'classic', 'minimal', 'bold']),
                'colors' => fake()->randomElements(['#FF5733', '#33FF57', '#3357FF'], 2),
                'tone' => fake()->randomElement(['professional', 'casual', 'playful']),
            ],
            'compliance_meta' => [
                'reviewed' => false,
                'reviewer' => null,
                'notes' => '',
            ],
            'final_copy' => [
                'headline' => fake()->catchPhrase(),
                'body' => fake()->paragraph(),
                'cta' => fake()->randomElement(['Learn More', 'Shop Now', 'Sign Up', 'Get Started']),
            ],
            'used_fields' => ['headline', 'body', 'cta'],
            'status' => fake()->randomElement(['draft', 'pending_review', 'approved', 'rejected']),
            'provider' => 'cmis',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'compliance_meta' => [
                'reviewed' => true,
                'reviewer' => fake()->name(),
                'notes' => 'Approved for use',
                'approved_at' => now()->toISOString(),
            ],
        ]);
    }

    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_review',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'compliance_meta' => [
                'reviewed' => true,
                'reviewer' => fake()->name(),
                'notes' => 'Does not meet brand guidelines',
                'rejected_at' => now()->toISOString(),
            ],
        ]);
    }
}
