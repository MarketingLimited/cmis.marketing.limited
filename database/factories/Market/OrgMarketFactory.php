<?php

namespace Database\Factories\Market;

use App\Models\Market\{OrgMarket, Market};
use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrgMarketFactory extends Factory
{
    protected $model = OrgMarket::class;

    public function definition(): array
    {
        return [
            'org_id' => Org::factory(),
            'market_id' => Market::factory(),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
