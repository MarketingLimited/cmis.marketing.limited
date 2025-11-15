<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private $orgIds;
    private $userIds;
    private $roleIds;
    private $channelIds;
    private $formatIds;
    private $integrationIds;
    private $campaignIds;

    /**
     * Create comprehensive demo data showing complete application workflow.
     */
    public function run(): void
    {
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        $this->loadReferenceData();
        $this->createUserOrgs();
        $this->createRolePermissions();
        $this->createOfferingsAndSegments();
        $this->createIntegrations();
        $this->createSocialAccounts();
        $this->createAdAccounts();
        $this->createCampaigns();
        $this->createCreativeAssets();
        $this->createContentPlans();
        $this->createSocialPosts();
        $this->createScheduledPosts();
        $this->createAdCampaigns();

        $this->command->info('✓ Demo data seeded successfully!');
        $this->command->info('');
        $this->command->info('Demo Organizations:');
        $this->command->info('  • TechVision Solutions (Technology, USD)');
        $this->command->info('  • الشركة العربية للتسويق (Arabic Marketing, SAR)');
        $this->command->info('  • FashionHub Retail (Fashion, EUR)');
        $this->command->info('  • HealthWell Clinic (Healthcare, AED)');
        $this->command->info('');
        $this->command->info('Demo Users (password: password):');
        $this->command->info('  • admin@cmis.test - System Admin');
        $this->command->info('  • sarah@techvision.com - Marketing Manager');
        $this->command->info('  • mohamed@arabic-marketing.com - Content Creator');
        $this->command->info('  • emma@fashionhub.com - Social Media Manager');
    }

    private function loadReferenceData()
    {
        // Load existing IDs
        $this->orgIds = DB::table('cmis.orgs')->pluck('org_id', 'name')->toArray();
        $this->userIds = DB::table('cmis.users')->pluck('user_id', 'email')->toArray();
        $this->roleIds = DB::table('cmis.roles')->pluck('role_id', 'role_code')->toArray();
        $this->channelIds = DB::table('public.channels')->pluck('channel_id', 'code')->toArray();

        // Load channel formats
        $formats = DB::table('public.channel_formats')
            ->join('public.channels', 'channel_formats.channel_id', '=', 'channels.channel_id')
            ->select('channels.code as channel_code', 'channel_formats.format_id', 'channel_formats.code as format_code')
            ->get();

        $this->formatIds = [];
        foreach ($formats as $format) {
            $this->formatIds[$format->channel_code][$format->format_code] = $format->format_id;
        }
    }

    private function createUserOrgs()
    {
        $userOrgs = [
            // Admin in all orgs
            ['user' => 'admin@cmis.test', 'org' => 'TechVision Solutions', 'role' => 'owner'],
            ['user' => 'admin@cmis.test', 'org' => 'الشركة العربية للتسويق', 'role' => 'owner'],
            ['user' => 'admin@cmis.test', 'org' => 'FashionHub Retail', 'role' => 'owner'],
            ['user' => 'admin@cmis.test', 'org' => 'HealthWell Clinic', 'role' => 'owner'],

            // TechVision team
            ['user' => 'sarah@techvision.com', 'org' => 'TechVision Solutions', 'role' => 'marketing_manager'],
            ['user' => 'maria@techvision.com', 'org' => 'TechVision Solutions', 'role' => 'content_creator'],

            // Arabic Marketing team
            ['user' => 'mohamed@arabic-marketing.com', 'org' => 'الشركة العربية للتسويق', 'role' => 'marketing_manager'],
            ['user' => 'ahmed@arabic-marketing.com', 'org' => 'الشركة العربية للتسويق', 'role' => 'social_manager'],

            // FashionHub team
            ['user' => 'emma@fashionhub.com', 'org' => 'FashionHub Retail', 'role' => 'social_manager'],

            // HealthWell team
            ['user' => 'david@healthwell.com', 'org' => 'HealthWell Clinic', 'role' => 'marketing_manager'],
        ];

        foreach ($userOrgs as $uo) {
            DB::table('cmis.user_orgs')->insert([
                'id' => Str::uuid(),
                'user_id' => $this->userIds[$uo['user']],
                'org_id' => $this->orgIds[$uo['org']],
                'role_id' => $this->roleIds[$uo['role']],
                'is_active' => true,
                'joined_at' => now()->subDays(rand(30, 90)),
                'invited_by' => $this->userIds['admin@cmis.test'],
                'last_accessed' => now()->subHours(rand(1, 48)),
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createRolePermissions()
    {
        $permissions = DB::table('cmis.permissions')->get();

        // Owner gets all permissions
        foreach ($permissions as $permission) {
            DB::table('cmis.role_permissions')->insert([
                'id' => Str::uuid(),
                'role_id' => $this->roleIds['owner'],
                'permission_id' => $permission->permission_id,
                'granted_at' => now(),
                'granted_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ]);
        }

        // Marketing Manager permissions
        $marketingPerms = $permissions->whereIn('category', ['campaigns', 'creative', 'content', 'social_media', 'advertising', 'analytics']);
        foreach ($marketingPerms as $permission) {
            if (!str_contains($permission->permission_code, 'delete')) {
                DB::table('cmis.role_permissions')->insert([
                    'id' => Str::uuid(),
                    'role_id' => $this->roleIds['marketing_manager'],
                    'permission_id' => $permission->permission_id,
                    'granted_at' => now(),
                    'granted_by' => null,
                    'deleted_at' => null,
                    'provider' => null,
                ]);
            }
        }

        // Content Creator permissions
        $contentPerms = $permissions->whereIn('category', ['content', 'creative'])->whereNotIn('permission_code', function($q) {
            return str_contains($q, 'delete') || str_contains($q, 'publish');
        });
        foreach ($contentPerms as $permission) {
            if (!str_contains($permission->permission_code, 'delete') && !str_contains($permission->permission_code, 'publish')) {
                DB::table('cmis.role_permissions')->insert([
                    'id' => Str::uuid(),
                    'role_id' => $this->roleIds['content_creator'],
                    'permission_id' => $permission->permission_id,
                    'granted_at' => now(),
                    'granted_by' => null,
                    'deleted_at' => null,
                    'provider' => null,
                ]);
            }
        }

        // Social Media Manager permissions
        $socialPerms = $permissions->where('category', 'social_media');
        foreach ($socialPerms as $permission) {
            DB::table('cmis.role_permissions')->insert([
                'id' => Str::uuid(),
                'role_id' => $this->roleIds['social_manager'],
                'permission_id' => $permission->permission_id,
                'granted_at' => now(),
                'granted_by' => null,
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createOfferingsAndSegments()
    {
        // TechVision offerings
        DB::table('cmis.offerings_old')->insert([
            ['offering_id' => Str::uuid(), 'org_id' => $this->orgIds['TechVision Solutions'], 'kind' => 'product', 'name' => 'CloudSync Pro', 'description' => 'Enterprise cloud synchronization platform', 'created_at' => now(), 'deleted_at' => null, 'provider' => null],
            ['offering_id' => Str::uuid(), 'org_id' => $this->orgIds['TechVision Solutions'], 'kind' => 'service', 'name' => 'Tech Consulting', 'description' => 'Expert technology consulting services', 'created_at' => now(), 'deleted_at' => null, 'provider' => null],
        ]);

        // FashionHub offerings
        DB::table('cmis.offerings_old')->insert([
            ['offering_id' => Str::uuid(), 'org_id' => $this->orgIds['FashionHub Retail'], 'kind' => 'product', 'name' => 'Summer Collection 2025', 'description' => 'Latest summer fashion trends', 'created_at' => now(), 'deleted_at' => null, 'provider' => null],
            ['offering_id' => Str::uuid(), 'org_id' => $this->orgIds['FashionHub Retail'], 'kind' => 'product', 'name' => 'Premium Accessories', 'description' => 'Luxury fashion accessories', 'created_at' => now(), 'deleted_at' => null, 'provider' => null],
        ]);

        // Segments
        DB::table('cmis.segments')->insert([
            ['segment_id' => Str::uuid(), 'org_id' => $this->orgIds['TechVision Solutions'], 'name' => 'Enterprise IT Directors', 'persona' => json_encode(['age_range' => '35-55', 'job_title' => 'IT Director', 'company_size' => '500+']), 'notes' => 'Decision makers for enterprise software', 'created_at' => now(), 'deleted_at' => null, 'provider' => null],
            ['segment_id' => Str::uuid(), 'org_id' => $this->orgIds['FashionHub Retail'], 'name' => 'Fashion Enthusiasts 18-35', 'persona' => json_encode(['age_range' => '18-35', 'interests' => ['fashion', 'lifestyle', 'trends']]), 'notes' => 'Young adults following fashion trends', 'created_at' => now(), 'deleted_at' => null, 'provider' => null],
        ]);
    }

    private function createIntegrations()
    {
        $this->integrationIds = [];

        $integrations = [
            // TechVision - Instagram
            [
                'id' => Str::uuid(),
                'org_id' => $this->orgIds['TechVision Solutions'],
                'platform' => 'instagram',
                'account_id' => 'techvision_official',
                'username' => 'techvision_official',
                'access_token' => 'demo_token_' . Str::random(32),
                'is_active' => true,
                'business_id' => 'business_' . rand(100000, 999999),
                'created_at' => now()->subDays(60),
                'created_by' => $this->userIds['sarah@techvision.com'],
                'updated_by' => null,
                'updated_at' => now()->subDays(60),
                'deleted_at' => null,
                'provider' => 'meta',
            ],
            // FashionHub - Instagram
            [
                'id' => Str::uuid(),
                'org_id' => $this->orgIds['FashionHub Retail'],
                'platform' => 'instagram',
                'account_id' => 'fashionhub_style',
                'username' => 'fashionhub_style',
                'access_token' => 'demo_token_' . Str::random(32),
                'is_active' => true,
                'business_id' => 'business_' . rand(100000, 999999),
                'created_at' => now()->subDays(90),
                'created_by' => $this->userIds['emma@fashionhub.com'],
                'updated_by' => null,
                'updated_at' => now()->subDays(90),
                'deleted_at' => null,
                'provider' => 'meta',
            ],
            // TechVision - Facebook Ads
            [
                'id' => Str::uuid(),
                'org_id' => $this->orgIds['TechVision Solutions'],
                'platform' => 'facebook_ads',
                'account_id' => 'act_123456789',
                'username' => null,
                'access_token' => 'demo_token_' . Str::random(32),
                'is_active' => true,
                'business_id' => 'business_' . rand(100000, 999999),
                'created_at' => now()->subDays(60),
                'created_by' => $this->userIds['sarah@techvision.com'],
                'updated_by' => null,
                'updated_at' => now()->subDays(60),
                'deleted_at' => null,
                'provider' => 'meta',
            ],
        ];

        foreach ($integrations as $integration) {
            DB::table('cmis.integrations')->insert($integration);
            $this->integrationIds[$integration['org_id']][$integration['platform']] = $integration['id'];
        }
    }

    private function createSocialAccounts()
    {
        $accounts = [
            [
                'id' => Str::uuid(),
                'org_id' => $this->orgIds['TechVision Solutions'],
                'integration_id' => $this->integrationIds[$this->orgIds['TechVision Solutions']]['instagram'],
                'account_external_id' => '17841405309211844',
                'username' => 'techvision_official',
                'display_name' => 'TechVision Solutions',
                'profile_picture_url' => 'https://example.com/profile.jpg',
                'biography' => 'Enterprise cloud solutions for modern businesses 🚀',
                'followers_count' => 12543,
                'follows_count' => 342,
                'media_count' => 156,
                'website' => 'https://techvision.com',
                'category' => 'Technology',
                'fetched_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
                'provider' => 'meta',
            ],
            [
                'id' => Str::uuid(),
                'org_id' => $this->orgIds['FashionHub Retail'],
                'integration_id' => $this->integrationIds[$this->orgIds['FashionHub Retail']]['instagram'],
                'account_external_id' => '17841405309211845',
                'username' => 'fashionhub_style',
                'display_name' => 'FashionHub | Style Inspiration',
                'profile_picture_url' => 'https://example.com/profile2.jpg',
                'biography' => 'Your daily dose of fashion inspiration ✨ | Shop the latest trends',
                'followers_count' => 45621,
                'follows_count' => 1234,
                'media_count' => 892,
                'website' => 'https://fashionhub.com',
                'category' => 'Fashion',
                'fetched_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
                'provider' => 'meta',
            ],
        ];

        foreach ($accounts as $account) {
            DB::table('cmis.social_accounts')->insert($account);
        }
    }

    private function createAdAccounts()
    {
        $adAccounts = [
            [
                'id' => Str::uuid(),
                'org_id' => $this->orgIds['TechVision Solutions'],
                'integration_id' => $this->integrationIds[$this->orgIds['TechVision Solutions']]['facebook_ads'],
                'account_external_id' => 'act_123456789',
                'name' => 'TechVision Ad Account',
                'currency' => 'USD',
                'timezone' => 'America/New_York',
                'spend_cap' => 50000.00,
                'status' => 'active',
                'created_at' => now()->subDays(60),
                'updated_at' => now(),
                'deleted_at' => null,
                'provider' => 'meta',
            ],
        ];

        foreach ($adAccounts as $account) {
            DB::table('cmis.ad_accounts')->insert($account);
        }
    }

    private function createCampaigns()
    {
        $this->campaignIds = [];

        $campaigns = [
            [
                'campaign_id' => Str::uuid(),
                'org_id' => $this->orgIds['TechVision Solutions'],
                'name' => 'CloudSync Pro Launch Campaign',
                'objective' => 'conversions',
                'status' => 'active',
                'start_date' => now()->subDays(15),
                'end_date' => now()->addDays(45),
                'budget' => 25000.00,
                'currency' => 'USD',
                'description' => 'Product launch campaign for CloudSync Pro targeting enterprise customers',
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(5),
                'context_id' => null,
                'creative_id' => null,
                'value_id' => null,
                'created_by' => $this->userIds['sarah@techvision.com'],
                'deleted_at' => null,
                'provider' => null,
                'deleted_by' => null,
            ],
            [
                'campaign_id' => Str::uuid(),
                'org_id' => $this->orgIds['FashionHub Retail'],
                'name' => 'Summer Collection 2025',
                'objective' => 'catalog_sales',
                'status' => 'active',
                'start_date' => now()->subDays(30),
                'end_date' => now()->addDays(60),
                'budget' => 15000.00,
                'currency' => 'EUR',
                'description' => 'Promote summer fashion collection across social media',
                'created_at' => now()->subDays(35),
                'updated_at' => now()->subDays(10),
                'context_id' => null,
                'creative_id' => null,
                'value_id' => null,
                'created_by' => $this->userIds['emma@fashionhub.com'],
                'deleted_at' => null,
                'provider' => null,
                'deleted_by' => null,
            ],
        ];

        foreach ($campaigns as $campaign) {
            DB::table('cmis.campaigns')->insert($campaign);
            $this->campaignIds[$campaign['org_id']][] = $campaign['campaign_id'];
        }
    }

    private function createCreativeAssets()
    {
        $assets = [
            [
                'asset_id' => Str::uuid(),
                'org_id' => $this->orgIds['TechVision Solutions'],
                'campaign_id' => $this->campaignIds[$this->orgIds['TechVision Solutions']][0],
                'channel_id' => $this->channelIds['instagram'] ?? null,
                'format_id' => $this->formatIds['instagram']['feed_square'] ?? null,
                'variation_tag' => 'A',
                'copy_block' => 'Transform your enterprise with CloudSync Pro',
                'art_direction' => json_encode([
                    'theme' => 'professional',
                    'colors' => ['#0066CC', '#FFFFFF'],
                    'style' => 'modern_minimal',
                ]),
                'compliance_meta' => json_encode(['approved' => true]),
                'final_copy' => json_encode([
                    'headline' => 'CloudSync Pro - Enterprise Cloud Platform',
                    'body' => 'Seamless collaboration for modern teams. Start your free trial today.',
                    'cta' => 'Learn More',
                ]),
                'used_fields' => json_encode(['headline', 'body', 'cta']),
                'compliance_report' => json_encode(['status' => 'approved']),
                'status' => 'approved',
                'created_at' => now()->subDays(18),
                'context_id' => null,
                'example_id' => null,
                'brief_id' => null,
                'creative_context_id' => null,
                'deleted_at' => null,
                'provider' => null,
                'deleted_by' => null,
            ],
        ];

        foreach ($assets as $asset) {
            DB::table('cmis.creative_assets')->insert($asset);
        }
    }

    private function createContentPlans()
    {
        $plans = [
            [
                'plan_id' => Str::uuid(),
                'org_id' => $this->orgIds['FashionHub Retail'],
                'campaign_id' => $this->campaignIds[$this->orgIds['FashionHub Retail']][0],
                'name' => 'Summer Collection Social Media Plan',
                'timeframe_daterange' => json_encode([
                    'start' => now()->subDays(30)->toDateString(),
                    'end' => now()->addDays(60)->toDateString(),
                ]),
                'strategy' => json_encode([
                    'objectives' => ['brand_awareness', 'sales'],
                    'posting_frequency' => 'daily',
                    'content_themes' => ['summer_trends', 'styling_tips', 'product_highlights'],
                ]),
                'created_at' => now()->subDays(35),
                'brief_id' => null,
                'creative_context_id' => null,
                'deleted_at' => null,
                'provider' => null,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('cmis.content_plans')->insert($plan);
        }
    }

    private function createSocialPosts()
    {
        $techIntegration = $this->integrationIds[$this->orgIds['TechVision Solutions']]['instagram'];
        $fashionIntegration = $this->integrationIds[$this->orgIds['FashionHub Retail']]['instagram'];

        $posts = [
            // TechVision posts
            [
                'id' => Str::uuid(),
                'org_id' => $this->orgIds['TechVision Solutions'],
                'integration_id' => $techIntegration,
                'post_external_id' => '18123456789012345',
                'caption' => '🚀 Introducing CloudSync Pro! The enterprise cloud platform built for modern teams. #CloudSync #Enterprise #Technology',
                'media_url' => 'https://example.com/media1.jpg',
                'permalink' => 'https://instagram.com/p/ABC123',
                'media_type' => 'IMAGE',
                'posted_at' => now()->subDays(10),
                'metrics' => json_encode([
                    'likes' => 234,
                    'comments' => 18,
                    'shares' => 12,
                    'saves' => 45,
                ]),
                'fetched_at' => now(),
                'created_at' => now()->subDays(10),
                'video_url' => null,
                'thumbnail_url' => null,
                'children_media' => null,
                'deleted_at' => null,
                'provider' => 'meta',
            ],
            // FashionHub posts
            [
                'id' => Str::uuid(),
                'org_id' => $this->orgIds['FashionHub Retail'],
                'integration_id' => $fashionIntegration,
                'post_external_id' => '18123456789012346',
                'caption' => '✨ Summer vibes are here! Check out our new collection. Link in bio! #SummerFashion #OOTD #Style',
                'media_url' => 'https://example.com/media2.jpg',
                'permalink' => 'https://instagram.com/p/DEF456',
                'media_type' => 'CAROUSEL_ALBUM',
                'posted_at' => now()->subDays(5),
                'metrics' => json_encode([
                    'likes' => 1253,
                    'comments' => 87,
                    'shares' => 34,
                    'saves' => 156,
                ]),
                'fetched_at' => now(),
                'created_at' => now()->subDays(5),
                'video_url' => null,
                'thumbnail_url' => null,
                'children_media' => json_encode([
                    ['media_url' => 'https://example.com/media2-1.jpg'],
                    ['media_url' => 'https://example.com/media2-2.jpg'],
                    ['media_url' => 'https://example.com/media2-3.jpg'],
                ]),
                'deleted_at' => null,
                'provider' => 'meta',
            ],
        ];

        foreach ($posts as $post) {
            DB::table('cmis.social_posts')->insert($post);
        }
    }

    private function createScheduledPosts()
    {
        $posts = [
            [
                'id' => Str::uuid(),
                'org_id' => $this->orgIds['TechVision Solutions'],
                'user_id' => $this->userIds['sarah@techvision.com'],
                'campaign_id' => $this->campaignIds[$this->orgIds['TechVision Solutions']][0],
                'platforms' => json_encode(['instagram', 'facebook']),
                'content' => 'Week 2 of CloudSync Pro launch! See how enterprise teams are transforming their workflows. 💼 #CloudSync #Productivity',
                'media' => json_encode([
                    ['url' => 'https://example.com/scheduled1.jpg', 'type' => 'image']
                ]),
                'scheduled_at' => now()->addDays(2)->setHour(10)->setMinute(0),
                'status' => 'scheduled',
                'published_at' => null,
                'published_ids' => null,
                'error_message' => null,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'org_id' => $this->orgIds['FashionHub Retail'],
                'user_id' => $this->userIds['emma@fashionhub.com'],
                'campaign_id' => $this->campaignIds[$this->orgIds['FashionHub Retail']][0],
                'platforms' => json_encode(['instagram']),
                'content' => '🌸 New arrivals just dropped! Swipe to see the hottest summer trends. Which is your favorite? #NewArrivals #SummerStyle',
                'media' => json_encode([
                    ['url' => 'https://example.com/scheduled2-1.jpg', 'type' => 'image'],
                    ['url' => 'https://example.com/scheduled2-2.jpg', 'type' => 'image'],
                    ['url' => 'https://example.com/scheduled2-3.jpg', 'type' => 'image'],
                ]),
                'scheduled_at' => now()->addDays(1)->setHour(14)->setMinute(0),
                'status' => 'scheduled',
                'published_at' => null,
                'published_ids' => null,
                'error_message' => null,
                'created_at' => now()->subHours(12),
                'updated_at' => now()->subHours(12),
                'deleted_at' => null,
            ],
        ];

        foreach ($posts as $post) {
            DB::table('cmis.scheduled_social_posts')->insert($post);
        }
    }

    private function createAdCampaigns()
    {
        $adAccount = DB::table('cmis.ad_accounts')
            ->where('org_id', $this->orgIds['TechVision Solutions'])
            ->first();

        if (!$adAccount) return;

        // Ad Campaign
        $adCampaignId = Str::uuid();
        DB::table('cmis.ad_campaigns')->insert([
            'id' => $adCampaignId,
            'org_id' => $this->orgIds['TechVision Solutions'],
            'integration_id' => $this->integrationIds[$this->orgIds['TechVision Solutions']]['facebook_ads'],
            'campaign_external_id' => '120212345678901',
            'name' => 'CloudSync Pro - Lead Generation',
            'objective' => 'OUTCOME_LEADS',
            'start_date' => now()->subDays(15)->toDateString(),
            'end_date' => now()->addDays(45)->toDateString(),
            'status' => 'ACTIVE',
            'budget' => 10000.00,
            'metrics' => json_encode([
                'spend' => 3456.78,
                'impressions' => 125000,
                'clicks' => 2340,
                'leads' => 156,
            ]),
            'fetched_at' => now(),
            'created_at' => now()->subDays(15),
            'deleted_at' => null,
            'provider' => 'meta',
            'deleted_by' => null,
        ]);

        // Ad Set
        $adSetId = Str::uuid();
        DB::table('cmis.ad_sets')->insert([
            'id' => $adSetId,
            'org_id' => $this->orgIds['TechVision Solutions'],
            'integration_id' => $this->integrationIds[$this->orgIds['TechVision Solutions']]['facebook_ads'],
            'campaign_external_id' => '120212345678901',
            'adset_external_id' => '120212345678902',
            'name' => 'IT Directors - US/UK',
            'status' => 'ACTIVE',
            'daily_budget' => 200.00,
            'start_date' => now()->subDays(15)->toDateString(),
            'end_date' => now()->addDays(45)->toDateString(),
            'billing_event' => 'IMPRESSIONS',
            'optimization_goal' => 'LEADS',
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(1),
            'deleted_at' => null,
            'provider' => 'meta',
            'deleted_by' => null,
        ]);

        // Individual Ad
        DB::table('cmis.ad_entities')->insert([
            'id' => Str::uuid(),
            'org_id' => $this->orgIds['TechVision Solutions'],
            'integration_id' => $this->integrationIds[$this->orgIds['TechVision Solutions']]['facebook_ads'],
            'adset_external_id' => '120212345678902',
            'ad_external_id' => '120212345678903',
            'name' => 'CloudSync Pro - Variant A',
            'status' => 'ACTIVE',
            'creative_id' => null,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(1),
            'deleted_at' => null,
            'provider' => 'meta',
            'deleted_by' => null,
        ]);
    }
}
