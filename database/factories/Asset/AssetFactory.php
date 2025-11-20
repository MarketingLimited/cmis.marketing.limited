<?php

namespace Database\Factories\Asset;

use App\Models\Asset\Asset;
use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset\Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileTypes = ['image', 'video', 'audio', 'document'];
        $assetTypes = ['creative', 'logo', 'banner', 'thumbnail'];

        return [
            'asset_id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'name' => fake()->words(3, true),
            'file_type' => fake()->randomElement($fileTypes),
            'file_path' => 'assets/' . fake()->uuid() . '.jpg',
            'file_size' => fake()->numberBetween(1024, 10485760), // 1KB to 10MB
            'mime_type' => 'image/jpeg',
            'asset_type' => fake()->randomElement($assetTypes),
            'status' => 'active',
            'metadata' => [
                'width' => fake()->numberBetween(100, 1920),
                'height' => fake()->numberBetween(100, 1080),
            ],
        ];
    }

    /**
     * Image asset.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_path' => 'assets/' . fake()->uuid() . '.jpg',
        ]);
    }

    /**
     * Video asset.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => 'video',
            'mime_type' => 'video/mp4',
            'file_path' => 'assets/' . fake()->uuid() . '.mp4',
        ]);
    }
}
