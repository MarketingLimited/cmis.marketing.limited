<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add new marketplace categories: social, content, compliance, finance
     */
    public function up(): void
    {
        $categories = [
            [
                'slug' => 'social',
                'name_key' => 'marketplace.categories.social',
                'description_key' => 'marketplace.categories.social_description',
                'icon' => 'fa-share-alt',
                'sort_order' => 25,
            ],
            [
                'slug' => 'content',
                'name_key' => 'marketplace.categories.content',
                'description_key' => 'marketplace.categories.content_description',
                'icon' => 'fa-file-alt',
                'sort_order' => 30,
            ],
            [
                'slug' => 'compliance',
                'name_key' => 'marketplace.categories.compliance',
                'description_key' => 'marketplace.categories.compliance_description',
                'icon' => 'fa-shield-alt',
                'sort_order' => 35,
            ],
            [
                'slug' => 'finance',
                'name_key' => 'marketplace.categories.finance',
                'description_key' => 'marketplace.categories.finance_description',
                'icon' => 'fa-dollar-sign',
                'sort_order' => 40,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('cmis.app_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                array_merge($category, [
                    'category_id' => \Illuminate\Support\Str::uuid()->toString(),
                    'is_active' => true,
                ])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('cmis.app_categories')
            ->whereIn('slug', ['social', 'content', 'compliance', 'finance'])
            ->delete();
    }
};
