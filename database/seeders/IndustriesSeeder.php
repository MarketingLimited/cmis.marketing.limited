<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndustriesSeeder extends Seeder
{
    /**
     * Seed industries for targeting and segmentation.
     */
    public function run(): void
    {
        $industries = [
            'Technology & Software',
            'E-commerce & Retail',
            'Healthcare & Medical',
            'Finance & Banking',
            'Real Estate',
            'Education & E-learning',
            'Food & Beverage',
            'Travel & Hospitality',
            'Fashion & Apparel',
            'Beauty & Cosmetics',
            'Automotive',
            'Entertainment & Media',
            'Sports & Fitness',
            'Home & Garden',
            'Professional Services',
            'Non-profit & Charity',
            'Manufacturing',
            'Construction',
            'Agriculture',
            'Energy & Utilities',
            'Telecommunications',
            'Insurance',
            'Legal Services',
            'Consulting',
            'Marketing & Advertising',
        ];

        foreach ($industries as $industry) {
            DB::table('public.industries')->insert([
                'name' => $industry
            ]);
        }

        $this->command->info('Industries seeded successfully!');
    }
}
