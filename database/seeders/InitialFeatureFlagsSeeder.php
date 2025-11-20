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
     * Configures initial launch setup:
     * - Meta + TikTok: Scheduling enabled
     * - Meta only: Paid campaigns enabled
     * - All other features disabled (coming soon)
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

        // Define initial launch configuration
        $launchConfig = [
            'scheduling' => [
                'meta' => true,      // ✅ Enabled
                'tiktok' => true,    // ✅ Enabled
                'google' => false,
                'linkedin' => false,
                'twitter' => false,
                'snapchat' => false,
            ],
            'paid_campaigns' => [
                'meta' => true,      // ✅ Enabled
                'google' => false,
                'tiktok' => false,
                'linkedin' => false,
                'twitter' => false,
                'snapchat' => false,
            ],
            'analytics' => [
                'meta' => false,
                'google' => false,
                'tiktok' => false,
                'linkedin' => false,
                'twitter' => false,
                'snapchat' => false,
            ],
            'organic_posts' => [
                'meta' => false,
                'google' => false,
                'tiktok' => false,
                'linkedin' => false,
                'twitter' => false,
                'snapchat' => false,
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

        $this->command->info('✅ Initial feature flags seeded successfully');
        $this->command->info('📊 Configuration:');
        $this->command->info('   ✓ Scheduling: Meta, TikTok');
        $this->command->info('   ✓ Paid Campaigns: Meta only');
        $this->command->info('   ⏳ All other features: Coming soon');
        $this->command->info('');
        $this->command->info('Total flags created: ' . count($flags));
    }
}
