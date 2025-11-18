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

        // First, truncate the table to ensure clean state
        DB::statement('TRUNCATE TABLE public.industries RESTART IDENTITY CASCADE');

        foreach ($industries as $industry) {
            // Use raw SQL with nextval to get the next sequence value for industry_id
            DB::statement(
                "INSERT INTO public.industries (industry_id, name) VALUES (nextval('industries_industry_id_seq'), ?)",
                [$industry]
            );
        }

        $this->command->info('Industries seeded successfully!');
    }
}