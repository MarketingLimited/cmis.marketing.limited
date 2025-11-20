<?php

namespace Database\Factories\Security;

use App\Models\Security\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Security\Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $resources = ['campaigns', 'content', 'assets', 'integrations', 'analytics'];
        $actions = ['view', 'create', 'edit', 'delete', 'publish'];

        $resource = fake()->randomElement($resources);
        $action = fake()->randomElement($actions);

        return [
            'permission_id' => (string) Str::uuid(),
            'permission_code' => "cmis.{$resource}.{$action}",
            'name' => ucfirst($action) . ' ' . ucfirst($resource),
            'description' => "Permission to {$action} {$resource}",
            'category' => $resource,
        ];
    }
}
