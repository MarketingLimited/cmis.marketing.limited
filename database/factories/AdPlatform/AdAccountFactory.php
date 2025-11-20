<?php

namespace Database\Factories\AdPlatform;

use App\Models\AdPlatform\AdAccount;
use App\Models\Core\Org;
use App\Models\Core\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdPlatform\AdAccount>
 */
class AdAccountFactory extends Factory
{
    protected $model = AdAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        $currencies = ['USD', 'EUR', 'GBP', 'CAD'];
        $timezones = ['America/New_York', 'America/Los_Angeles', 'Europe/London', 'Asia/Tokyo'];

        return [
            'id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'integration_id' => Integration::factory(),
            'account_external_id' => 'act_' . fake()->numerify('##########'),
            'name' => fake()->company() . ' Ad Account',
            'currency' => fake()->randomElement($currencies),
            'timezone' => fake()->randomElement($timezones),
            'spend_cap' => fake()->randomFloat(2, 1000, 100000),
            'status' => fake()->randomElement(['active', 'inactive', 'pending']),
            'provider' => fake()->randomElement($providers),
        ];
    }

    /**
     * Indicate that the account is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the account is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Set the account provider.
     */
    public function forProvider(string $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
        ]);
    }
}
