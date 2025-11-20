<?php

namespace Database\Factories\Budget;

use App\Models\Budget\Budget;
use App\Models\Core\Org;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        $periodStart = fake()->dateTimeBetween('-2 months', 'now');
        $periodEnd = fake()->dateTimeBetween($periodStart, '+6 months');
        $totalAmount = fake()->randomFloat(2, 5000, 500000);
        $spentAmount = fake()->randomFloat(2, 0, $totalAmount * 0.8);

        return [
            'budget_id' => (string) Str::uuid(),
            'org_id' => Org::factory(),
            'campaign_id' => Campaign::factory(),
            'total_amount' => $totalAmount,
            'spent_amount' => $spentAmount,
            'currency' => 'USD',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ];
    }

    public function monthly(): static
    {
        $start = now()->startOfMonth();

        return $this->state(fn (array $attributes) => [
            'period_start' => $start,
            'period_end' => $start->copy()->endOfMonth(),
        ]);
    }

    public function quarterly(): static
    {
        $start = now()->startOfQuarter();

        return $this->state(fn (array $attributes) => [
            'period_start' => $start,
            'period_end' => $start->copy()->endOfQuarter(),
        ]);
    }

    public function yearly(): static
    {
        $start = now()->startOfYear();

        return $this->state(fn (array $attributes) => [
            'period_start' => $start,
            'period_end' => $start->copy()->endOfYear(),
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(function (array $attributes) {
            $total = $attributes['total_amount'];

            return [
                'spent_amount' => $total,
            ];
        });
    }

    public function underutilized(): static
    {
        return $this->state(function (array $attributes) {
            $total = $attributes['total_amount'];

            return [
                'spent_amount' => $total * 0.1,
            ];
        });
    }
}
