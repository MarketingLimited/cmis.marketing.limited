<?php

namespace Database\Factories\Team;

use App\Models\Team\TeamMember;
use App\Models\Core\{User, Org};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TeamMemberFactory extends Factory
{
    protected $model = TeamMember::class;

    public function definition(): array
    {
        return [
            'member_id' => (string) Str::uuid(),
            'team_member_id' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'org_id' => Org::factory(),
            'role' => fake()->randomElement(['owner', 'admin', 'editor', 'viewer']),
            'is_active' => true,
            'joined_at' => now(),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'owner',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function editor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'editor',
        ]);
    }

    public function viewer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'viewer',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
