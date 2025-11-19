<?php

namespace Database\Factories\Knowledge;

use App\Models\Knowledge\KnowledgeBase;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class KnowledgeBaseFactory extends Factory
{
    protected $model = KnowledgeBase::class;

    public function definition(): array
    {
        return [
            'knowledge_id' => (string) Str::uuid(),
            'org_id' => (string) Str::uuid(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'category' => fake()->randomElement(['product', 'service', 'general']),
        ];
    }
}
