<?php

namespace Database\Factories;

use App\Models\Offering;
use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offering>
 */
class OfferingFactory extends Factory
{
    protected $model = Offering::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $kinds = ['product', 'service', 'bundle', 'subscription'];

        return [
            'offering_id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'kind' => fake()->randomElement($kinds),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Product offering.
     */
    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => 'product',
        ]);
    }

    /**
     * Service offering.
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => 'service',
        ]);
    }
}
