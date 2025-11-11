<?php

namespace Database\Factories\Core;

use App\Models\Core\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'role_id' => (string) Str::uuid(),
            'role_code' => fake()->unique()->word(),
            'role_name' => fake()->jobTitle(),
            'description' => fake()->sentence(),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_code' => 'owner',
            'role_name' => 'Owner',
            'description' => 'Organization owner with full access',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_code' => 'admin',
            'role_name' => 'Administrator',
            'description' => 'Administrator with management access',
        ]);
    }

    public function member(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_code' => 'member',
            'role_name' => 'Member',
            'description' => 'Regular member with basic access',
        ]);
    }
}
