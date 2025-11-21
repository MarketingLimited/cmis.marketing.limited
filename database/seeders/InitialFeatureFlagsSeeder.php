<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InitialFeatureFlagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * PHASE 1 LAUNCH CONFIGURATION (Based on weakness analysis 2025-11-21)
     * ========================================================================
     * Strategy: Focus on Meta platform only, disable all other platforms
     *
     * Enabled Features:
     * - ✅ Meta Paid Campaigns (Facebook/Instagram Ads)
     * - ✅ Meta Analytics & Reporting
     *
     * Disabled Features (Coming in Phase 2+):
     * - ⏳ Content Scheduling (all platforms) - Deferred to Phase 2
     * - ⏳ Organic Posts (all platforms) - Deferred to Phase 2
     * - ⏳ Google Ads - Phase 2
     * - ⏳ TikTok Ads - Phase 2
     * - ⏳ LinkedIn Ads - Phase 3
     * - ⏳ Twitter Ads - Phase 3
     * - ⏳ Snapchat Ads - Phase 3
     *
     * Rationale: Reduce complexity, focus on core value proposition,
     * improve test pass rate, enable faster iteration.
     *
     * @return void
     */
    public function run()
    {
        // Set admin context to bypass RLS
        DB::statement("SET LOCAL app.is_admin = true");

        $platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        $features = ['scheduling', 'paid_campaigns', 'analytics', 'organic_posts'];

        $featureDescriptions = [
            'scheduling' => [
                'ar' => 'جدولة المنشورات',
                'en' => 'Post scheduling and content calendar',
            ],
            'paid_campaigns' => [
                'ar' => 'إدارة الحملات الإعلانية الممولة',
                'en' => 'Paid advertising campaign management',
            ],
            'analytics' => [
                'ar' => 'تحليلات الأداء والتقارير',
                'en' => 'Performance analytics and reporting',
            ],
            'organic_posts' => [
                'ar' => 'المنشورات العضوية',
                'en' => 'Organic social media posts',
            ],
        ];

        $platformNames = [
            'meta' => 'Meta (Facebook & Instagram)',
            'google' => 'Google Ads',
            'tiktok' => 'TikTok Ads',
            'linkedin' => 'LinkedIn Ads',
            'twitter' => 'Twitter Ads',
            'snapchat' => 'Snapchat Ads',
        ];

        // PHASE 1 LAUNCH CONFIGURATION
        // Only Meta platform with paid campaigns and analytics
        $launchConfig = [
            'scheduling' => [
                'meta' => false,     // ⏳ Phase 2 - Deferred to simplify Phase 1
                'tiktok' => false,   // ⏳ Phase 2
                'google' => false,   // ⏳ Phase 3
                'linkedin' => false, // ⏳ Phase 3
                'twitter' => false,  // ⏳ Phase 3
                'snapchat' => false, // ⏳ Phase 3
            ],
            'paid_campaigns' => [
                'meta' => true,      // ✅ PHASE 1 - Core feature
                'google' => false,   // ⏳ Phase 2 - Second priority platform
                'tiktok' => false,   // ⏳ Phase 2 - Growing platform
                'linkedin' => false, // ⏳ Phase 3 - B2B focus
                'twitter' => false,  // ⏳ Phase 3
                'snapchat' => false, // ⏳ Phase 3
            ],
            'analytics' => [
                'meta' => true,      // ✅ PHASE 1 - Essential for campaign tracking
                'google' => false,   // ⏳ Phase 2
                'tiktok' => false,   // ⏳ Phase 2
                'linkedin' => false, // ⏳ Phase 3
                'twitter' => false,  // ⏳ Phase 3
                'snapchat' => false, // ⏳ Phase 3
            ],
            'organic_posts' => [
                'meta' => false,     // ⏳ Phase 2 - Will come with scheduling
                'google' => false,   // N/A - Google doesn't have organic posts
                'tiktok' => false,   // ⏳ Phase 2
                'linkedin' => false, // ⏳ Phase 3
                'twitter' => false,  // ⏳ Phase 3
                'snapchat' => false, // ⏳ Phase 3
            ],
        ];

        $flags = [];
        $now = now();

        foreach ($features as $feature) {
            foreach ($platforms as $platform) {
                $enabled = $launchConfig[$feature][$platform] ?? false;

                $flags[] = [
                    'id' => Str::uuid()->toString(),
                    'feature_key' => "{$feature}.{$platform}.enabled",
                    'scope_type' => 'system',
                    'scope_id' => null,
                    'value' => $enabled,
                    'description' => sprintf(
                        '%s for %s | %s',
                        $featureDescriptions[$feature]['en'],
                        $platformNames[$platform],
                        $featureDescriptions[$feature]['ar']
                    ),
                    'metadata' => json_encode([
                        'configured_at' => $now->toIso8601String(),
                        'initial_launch' => true,
                        'status' => $enabled ? 'enabled_at_launch' : 'coming_soon',
                    ]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Insert all flags in one batch
        DB::table('cmis.feature_flags')->insert($flags);

        $this->command->info('✅ PHASE 1 Feature flags seeded successfully');
        $this->command->info('');
        $this->command->info('📊 ENABLED Features (Phase 1):');
        $this->command->info('   ✅ Meta Paid Campaigns (Facebook/Instagram Ads)');
        $this->command->info('   ✅ Meta Analytics & Reporting');
        $this->command->info('');
        $this->command->info('⏳ DISABLED Features (Coming in Phase 2+):');
        $this->command->info('   ⏳ Content Scheduling (all platforms)');
        $this->command->info('   ⏳ Organic Posts (all platforms)');
        $this->command->info('   ⏳ Google Ads, TikTok Ads (Phase 2)');
        $this->command->info('   ⏳ LinkedIn, Twitter, Snapchat Ads (Phase 3)');
        $this->command->info('');
        $this->command->info('📈 Strategy: Focus on core value, improve quality, reduce complexity');
        $this->command->info('Total flags created: ' . count($flags));
    }
}
