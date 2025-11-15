<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferenceDataSeeder extends Seeder
{
    /**
     * Seed reference data for awareness stages, funnel stages, tones, strategies, and KPIs.
     */
    public function run(): void
    {
        // Awareness Stages
        $awarenessStages = [
            'unaware',
            'problem_aware',
            'solution_aware',
            'product_aware',
            'most_aware',
        ];

        foreach ($awarenessStages as $stage) {
            DB::table('public.awareness_stages')->insert(['stage' => $stage]);
        }

        // Funnel Stages
        $funnelStages = [
            'top_of_funnel',
            'middle_of_funnel',
            'bottom_of_funnel',
            'retention',
            'advocacy',
        ];

        foreach ($funnelStages as $stage) {
            DB::table('public.funnel_stages')->insert(['stage' => $stage]);
        }

        // Tones
        $tones = [
            'professional',
            'friendly',
            'casual',
            'formal',
            'humorous',
            'inspirational',
            'educational',
            'urgent',
            'empathetic',
            'confident',
            'playful',
            'authoritative',
        ];

        foreach ($tones as $tone) {
            DB::table('public.tones')->insert(['tone' => $tone]);
        }

        // Strategies
        $strategies = [
            'content_marketing',
            'social_media_marketing',
            'email_marketing',
            'influencer_marketing',
            'paid_advertising',
            'seo',
            'video_marketing',
            'community_building',
            'retargeting',
            'growth_hacking',
        ];

        foreach ($strategies as $strategy) {
            DB::table('public.strategies')->insert(['strategy' => $strategy]);
        }

        // KPIs
        $kpis = [
            ['kpi' => 'impressions', 'description' => 'Number of times content was displayed'],
            ['kpi' => 'reach', 'description' => 'Number of unique people who saw your content'],
            ['kpi' => 'engagement_rate', 'description' => 'Percentage of people who engaged with content'],
            ['kpi' => 'click_through_rate', 'description' => 'Percentage of people who clicked'],
            ['kpi' => 'conversion_rate', 'description' => 'Percentage of people who completed desired action'],
            ['kpi' => 'cost_per_click', 'description' => 'Average cost for each click'],
            ['kpi' => 'cost_per_acquisition', 'description' => 'Average cost to acquire a customer'],
            ['kpi' => 'return_on_ad_spend', 'description' => 'Revenue generated per dollar spent'],
            ['kpi' => 'customer_lifetime_value', 'description' => 'Total value of a customer over their lifetime'],
            ['kpi' => 'follower_growth', 'description' => 'Rate of new followers gained'],
            ['kpi' => 'video_completion_rate', 'description' => 'Percentage of people who watched entire video'],
            ['kpi' => 'bounce_rate', 'description' => 'Percentage of visitors who leave after one page'],
        ];

        foreach ($kpis as $kpi) {
            DB::table('public.kpis')->insert($kpi);
        }

        $this->command->info('Reference data seeded successfully!');
    }
}
