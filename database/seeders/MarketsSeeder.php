<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarketsSeeder extends Seeder
{
    /**
     * Seed markets with language and currency information.
     */
    public function run(): void
    {
        $markets = [
            // Middle East & North Africa
            ['market_name' => 'Saudi Arabia', 'language_code' => 'ar', 'currency_code' => 'SAR', 'text_direction' => 'rtl'],
            ['market_name' => 'United Arab Emirates', 'language_code' => 'ar', 'currency_code' => 'AED', 'text_direction' => 'rtl'],
            ['market_name' => 'Egypt', 'language_code' => 'ar', 'currency_code' => 'EGP', 'text_direction' => 'rtl'],
            ['market_name' => 'Kuwait', 'language_code' => 'ar', 'currency_code' => 'KWD', 'text_direction' => 'rtl'],
            ['market_name' => 'Qatar', 'language_code' => 'ar', 'currency_code' => 'QAR', 'text_direction' => 'rtl'],
            ['market_name' => 'Bahrain', 'language_code' => 'ar', 'currency_code' => 'BHD', 'text_direction' => 'rtl'],
            ['market_name' => 'Oman', 'language_code' => 'ar', 'currency_code' => 'OMR', 'text_direction' => 'rtl'],
            ['market_name' => 'Jordan', 'language_code' => 'ar', 'currency_code' => 'JOD', 'text_direction' => 'rtl'],
            ['market_name' => 'Lebanon', 'language_code' => 'ar', 'currency_code' => 'LBP', 'text_direction' => 'rtl'],
            ['market_name' => 'Morocco', 'language_code' => 'ar', 'currency_code' => 'MAD', 'text_direction' => 'rtl'],

            // North America
            ['market_name' => 'United States', 'language_code' => 'en', 'currency_code' => 'USD', 'text_direction' => 'ltr'],
            ['market_name' => 'Canada', 'language_code' => 'en', 'currency_code' => 'CAD', 'text_direction' => 'ltr'],

            // Europe
            ['market_name' => 'United Kingdom', 'language_code' => 'en', 'currency_code' => 'GBP', 'text_direction' => 'ltr'],
            ['market_name' => 'Germany', 'language_code' => 'de', 'currency_code' => 'EUR', 'text_direction' => 'ltr'],
            ['market_name' => 'France', 'language_code' => 'fr', 'currency_code' => 'EUR', 'text_direction' => 'ltr'],
            ['market_name' => 'Spain', 'language_code' => 'es', 'currency_code' => 'EUR', 'text_direction' => 'ltr'],
            ['market_name' => 'Italy', 'language_code' => 'it', 'currency_code' => 'EUR', 'text_direction' => 'ltr'],

            // Asia Pacific
            ['market_name' => 'India', 'language_code' => 'en', 'currency_code' => 'INR', 'text_direction' => 'ltr'],
            ['market_name' => 'Singapore', 'language_code' => 'en', 'currency_code' => 'SGD', 'text_direction' => 'ltr'],
            ['market_name' => 'Australia', 'language_code' => 'en', 'currency_code' => 'AUD', 'text_direction' => 'ltr'],
        ];

        // First, truncate the table to ensure clean state
        DB::statement('TRUNCATE TABLE public.markets RESTART IDENTITY CASCADE');

        foreach ($markets as $market) {
            // Use raw SQL with nextval to get the next sequence value for market_id
            DB::statement(
                "INSERT INTO public.markets (market_id, market_name, language_code, currency_code, text_direction)
                 VALUES (nextval('markets_market_id_seq'), ?, ?, ?, ?)",
                [
                    $market['market_name'],
                    $market['language_code'],
                    $market['currency_code'],
                    $market['text_direction']
                ]
            );
        }

        $this->command->info('Markets seeded successfully!');
    }
}