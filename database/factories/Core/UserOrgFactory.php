<?php

namespace Database\Factories\Core;

use App\Models\Core\{UserOrg, User, Org, Role};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserOrgFactory extends Factory
{
    protected $model = UserOrg::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'org_id' => Org::factory(),
            'role_id' => Role::factory(),
            'is_active' => true,
            'joined_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
