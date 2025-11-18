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
        $this->createCreativeBriefs();
        $this->createPerformanceMetrics();
        $this->createPublishingQueues();
        $this->createInboxItems();
        $this->createPostApprovals();
        $this->createABTests();
        $this->createAudienceTemplates();
        $this->createNotifications();
        $this->createUserActivities();
        $this->createTeamInvitations();

        $this->command->info('✓ Comprehensive demo data seeded successfully!');
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
                'invited_by' => null, // Can't reference users.id (bigint) from invited_by (uuid)
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

        // Segments (no created_at/updated_at columns)
        DB::table('cmis.segments')->insert([
            ['segment_id' => Str::uuid(), 'org_id' => $this->orgIds['TechVision Solutions'], 'name' => 'Enterprise IT Directors', 'persona' => json_encode(['age_range' => '35-55', 'job_title' => 'IT Director', 'company_size' => '500+']), 'notes' => 'Decision makers for enterprise software', 'deleted_at' => null, 'provider' => null],
            ['segment_id' => Str::uuid(), 'org_id' => $this->orgIds['FashionHub Retail'], 'name' => 'Fashion Enthusiasts 18-35', 'persona' => json_encode(['age_range' => '18-35', 'interests' => ['fashion', 'lifestyle', 'trends']]), 'notes' => 'Young adults following fashion trends', 'deleted_at' => null, 'provider' => null],
        ]);
    }

    private function createIntegrations()
    {
        $this->integrationIds = [];

        $integrations = [
            // TechVision - Instagram
            [
                'integration_id' => Str::uuid(),
                'org_id' => $this->orgIds['TechVision Solutions'],
                'platform' => 'instagram',
                'account_id' => 'techvision_official',
                'username' => 'techvision_official',
                'access_token' => 'demo_token_' . Str::random(32),
                'is_active' => true,
                'business_id' => 'business_' . rand(100000, 999999),
                'created_at' => now()->subDays(60),
                'created_by' => null, // Can't reference users.id (bigint) from created_by (uuid)
                'updated_by' => null,
                'updated_at' => now()->subDays(60),
                'deleted_at' => null,
                'provider' => 'meta',
            ],
            // FashionHub - Instagram
            [
                'integration_id' => Str::uuid(),
                'org_id' => $this->orgIds['FashionHub Retail'],
                'platform' => 'instagram',
                'account_id' => 'fashionhub_style',
                'username' => 'fashionhub_style',
                'access_token' => 'demo_token_' . Str::random(32),
                'is_active' => true,
                'business_id' => 'business_' . rand(100000, 999999),
                'created_at' => now()->subDays(90),
                'created_by' => null, // Can't reference users.id (bigint) from created_by (uuid)
                'updated_by' => null,
                'updated_at' => now()->subDays(90),
                'deleted_at' => null,
                'provider' => 'meta',
            ],
            // TechVision - Facebook Ads
            [
                'integration_id' => Str::uuid(),
                'org_id' => $this->orgIds['TechVision Solutions'],
                'platform' => 'facebook_ads',
                'account_id' => 'act_123456789',
                'username' => null,
                'access_token' => 'demo_token_' . Str::random(32),
                'is_active' => true,
                'business_id' => 'business_' . rand(100000, 999999),
                'created_at' => now()->subDays(60),
                'created_by' => null, // Can't reference users.id (bigint) from created_by (uuid)
                'updated_by' => null,
                'updated_at' => now()->subDays(60),
                'deleted_at' => null,
                'provider' => 'meta',
            ],
        ];

        foreach ($integrations as $integration) {
            DB::table('cmis.integrations')->insert($integration);
            $this->integrationIds[$integration['org_id']][$integration['platform']] = $integration['integration_id'];
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
                'timeframe_daterange' => DB::raw("'[" . now()->subDays(30)->toDateString() . "," . now()->addDays(60)->toDateString() . "]'::daterange"),
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

    private function createCreativeBriefs()
    {
        $briefs = [
            [
                'brief_id' => Str::uuid(),
                'org_id' => $this->orgIds['TechVision Solutions'],
                'name' => 'CloudSync Pro Launch Brief',
                'brief_data' => json_encode([
                    'objective' => 'Drive awareness and trial signups for CloudSync Pro',
                    'target_audience' => 'Enterprise IT Directors and CTOs',
                    'key_messages' => [
                        'Seamless cloud synchronization for enterprise teams',
                        'Bank-grade security and compliance',
                        '99.99% uptime guarantee'
                    ],
                    'brand_guidelines' => [
                        'tone' => 'professional, trustworthy, innovative',
                        'colors' => ['#0066CC', '#FFFFFF', '#F5F5F5'],
                        'fonts' => ['Inter', 'Roboto']
                    ],
                    'deliverables' => [
                        'Social media posts (Instagram, Facebook, LinkedIn)',
                        'Ad creatives for Facebook/Instagram',
                        'Landing page copy'
                    ],
                    'timeline' => '3 weeks',
                    'budget' => '$25,000'
                ]),
                'created_at' => now()->subDays(30),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'brief_id' => Str::uuid(),
                'org_id' => $this->orgIds['FashionHub Retail'],
                'name' => 'Summer Collection 2025 Creative Brief',
                'brief_data' => json_encode([
                    'objective' => 'Launch summer collection and drive online sales',
                    'target_audience' => 'Fashion-forward women aged 18-35',
                    'key_messages' => [
                        'Trendy, affordable summer fashion',
                        'New arrivals every week',
                        'Free shipping on orders over €50'
                    ],
                    'brand_guidelines' => [
                        'tone' => 'playful, inspiring, trendy',
                        'colors' => ['#FF6B9D', '#FFA07A', '#FFFFFF'],
                        'style' => 'bright, vibrant, lifestyle photography'
                    ],
                    'deliverables' => [
                        'Instagram carousel posts',
                        'Instagram Stories',
                        'Product photography',
                        'Lifestyle shots'
                    ],
                    'timeline' => '2 months',
                    'budget' => '€15,000'
                ]),
                'created_at' => now()->subDays(40),
                'deleted_at' => null,
                'provider' => null,
            ],
        ];

        foreach ($briefs as $brief) {
            DB::table('cmis.creative_briefs')->insert($brief);
        }
    }

    private function createPerformanceMetrics()
    {
        $campaigns = DB::table('cmis.campaigns')->get();

        foreach ($campaigns as $campaign) {
            // Create multiple metric entries to show trends
            $baseMetrics = [
                'impressions' => rand(50000, 200000),
                'clicks' => rand(1000, 5000),
                'conversions' => rand(50, 300),
                'spend' => rand(1000, 5000)
            ];

            DB::table('cmis.performance_metrics')->insert([
                [
                    'metric_id' => Str::uuid(),
                    'org_id' => $campaign->org_id,
                    'campaign_id' => $campaign->campaign_id,
                    'output_id' => null,
                    'kpi' => 'impressions',
                    'observed' => $baseMetrics['impressions'],
                    'target' => $baseMetrics['impressions'] * 1.2,
                    'baseline' => $baseMetrics['impressions'] * 0.8,
                    'observed_at' => now()->subDays(7),
                    'deleted_at' => null,
                    'provider' => null,
                ],
                [
                    'metric_id' => Str::uuid(),
                    'org_id' => $campaign->org_id,
                    'campaign_id' => $campaign->campaign_id,
                    'output_id' => null,
                    'kpi' => 'click_through_rate',
                    'observed' => round(($baseMetrics['clicks'] / $baseMetrics['impressions']) * 100, 2),
                    'target' => 3.5,
                    'baseline' => 2.0,
                    'observed_at' => now()->subDays(7),
                    'deleted_at' => null,
                    'provider' => null,
                ],
                [
                    'metric_id' => Str::uuid(),
                    'org_id' => $campaign->org_id,
                    'campaign_id' => $campaign->campaign_id,
                    'output_id' => null,
                    'kpi' => 'conversion_rate',
                    'observed' => round(($baseMetrics['conversions'] / $baseMetrics['clicks']) * 100, 2),
                    'target' => 8.0,
                    'baseline' => 4.5,
                    'observed_at' => now()->subDays(7),
                    'deleted_at' => null,
                    'provider' => null,
                ],
            ]);
        }
    }

    private function createPublishingQueues()
    {
        $socialAccounts = DB::table('cmis.social_accounts')->get();

        foreach ($socialAccounts as $account) {
            DB::table('cmis.publishing_queues')->insert([
                'queue_id' => Str::uuid(),
                'org_id' => $account->org_id,
                'social_account_id' => $account->id,
                'weekdays_enabled' => '1111100', // Mon-Fri enabled (7 bits for each day)
                'time_slots' => json_encode([
                    '09:00', '12:00', '15:00', '18:00'
                ]),
                'timezone' => 'UTC',
                'is_active' => true,
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
            ]);
        }
    }

    private function createInboxItems()
    {
        $socialAccounts = DB::table('cmis.social_accounts')->get();

        foreach ($socialAccounts as $account) {
            // Create some inbox items (comments, messages)
            DB::table('cmis.inbox_items')->insert([
                [
                    'item_id' => Str::uuid(),
                    'org_id' => $account->org_id,
                    'social_account_id' => $account->id,
                    'item_type' => 'comment',
                    'platform' => 'instagram',
                    'external_id' => 'comment_' . Str::random(16),
                    'content' => 'Love this! Where can I buy it?',
                    'sender_name' => 'Sarah Martinez',
                    'sender_id' => 'user_' . rand(100000, 999999),
                    'sender_avatar_url' => 'https://example.com/avatar1.jpg',
                    'needs_reply' => true,
                    'assigned_to' => null,
                    'status' => 'pending',
                    'reply_content' => null,
                    'replied_at' => null,
                    'sentiment' => 'positive',
                    'sentiment_score' => 0.85,
                    'platform_created_at' => now()->subHours(3),
                    'created_at' => now()->subHours(3),
                    'updated_at' => now()->subHours(3),
                ],
                [
                    'item_id' => Str::uuid(),
                    'org_id' => $account->org_id,
                    'social_account_id' => $account->id,
                    'item_type' => 'message',
                    'platform' => 'instagram',
                    'external_id' => 'message_' . Str::random(16),
                    'content' => 'Do you ship internationally?',
                    'sender_name' => 'John Smith',
                    'sender_id' => 'user_' . rand(100000, 999999),
                    'sender_avatar_url' => 'https://example.com/avatar2.jpg',
                    'needs_reply' => true,
                    'assigned_to' => null,
                    'status' => 'pending',
                    'reply_content' => null,
                    'replied_at' => null,
                    'sentiment' => 'neutral',
                    'sentiment_score' => 0.5,
                    'platform_created_at' => now()->subHours(1),
                    'created_at' => now()->subHours(1),
                    'updated_at' => now()->subHours(1),
                ],
            ]);
        }
    }

    private function createPostApprovals()
    {
        $scheduledPosts = DB::table('cmis.scheduled_social_posts')->get();

        foreach ($scheduledPosts as $post) {
            // Get a manager from the org to approve
            $manager = DB::table('cmis.user_orgs')
                ->join('cmis.roles', 'user_orgs.role_id', '=', 'roles.role_id')
                ->where('user_orgs.org_id', $post->org_id)
                ->where('roles.role_code', 'marketing_manager')
                ->first();

            if (!$manager) continue;

            DB::table('cmis.post_approvals')->insert([
                'approval_id' => Str::uuid(),
                'post_id' => $post->id,
                'requested_by' => $post->user_id,
                'assigned_to' => $manager->user_id,
                'status' => 'pending',
                'comments' => null,
                'reviewed_at' => null,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ]);
        }
    }

    private function createABTests()
    {
        $adAccount = DB::table('cmis.ad_accounts')
            ->where('org_id', $this->orgIds['TechVision Solutions'])
            ->first();

        if (!$adAccount) return;

        // Create an A/B test
        $testId = Str::uuid();
        DB::table('cmis.ab_tests')->insert([
            'ab_test_id' => $testId,
            'ad_account_id' => $adAccount->id,
            'entity_type' => 'creative',
            'entity_id' => null,
            'test_name' => 'Headline Variation Test',
            'test_type' => 'split',
            'test_status' => 'running',
            'hypothesis' => 'Benefit-focused headlines will outperform feature-focused headlines',
            'metric_to_optimize' => 'click_through_rate',
            'budget_per_variation' => 500.00,
            'test_duration_days' => 14,
            'min_sample_size' => 1000,
            'confidence_level' => 0.95, // 95% as decimal
            'winner_variation_id' => null,
            'config' => json_encode([
                'traffic_split' => '50/50',
                'significance_threshold' => 0.05
            ]),
            'started_at' => now()->subDays(7),
            'scheduled_end_at' => now()->addDays(7),
            'completed_at' => null,
            'stop_reason' => null,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(7),
        ]);

        // Create variations
        DB::table('cmis.ab_test_variations')->insert([
            [
                'variation_id' => Str::uuid(),
                'ab_test_id' => $testId,
                'variation_name' => 'Control - Feature Focused',
                'is_control' => true,
                'entity_id' => null,
                'variation_config' => json_encode([
                    'headline' => 'CloudSync Pro - Enterprise Cloud Platform',
                    'description' => 'Advanced features for modern teams'
                ]),
                'traffic_allocation' => 50,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(7),
            ],
            [
                'variation_id' => Str::uuid(),
                'ab_test_id' => $testId,
                'variation_name' => 'Variation A - Benefit Focused',
                'is_control' => false,
                'entity_id' => null,
                'variation_config' => json_encode([
                    'headline' => 'Transform Team Collaboration Today',
                    'description' => 'Save 10+ hours per week with automated workflows'
                ]),
                'traffic_allocation' => 50,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(7),
            ],
        ]);
    }

    private function createAudienceTemplates()
    {
        $templates = [
            [
                'template_id' => Str::uuid(),
                'org_id' => $this->orgIds['TechVision Solutions'],
                'name' => 'Enterprise IT Decision Makers',
                'description' => 'IT Directors, CTOs, and Technology Leaders at enterprises',
                'targeting_criteria' => json_encode([
                    'age_range' => ['min' => 30, 'max' => 55],
                    'job_titles' => ['IT Director', 'CTO', 'VP Technology', 'Technology Manager'],
                    'company_size' => ['min' => 500],
                    'interests' => ['Enterprise Software', 'Cloud Computing', 'IT Management'],
                    'industries' => ['Technology', 'Finance', 'Healthcare']
                ]),
                'platforms' => json_encode(['facebook', 'linkedin', 'google_ads']),
                'usage_count' => 3,
                'last_used_at' => now()->subDays(5),
                'created_by' => $this->userIds['sarah@techvision.com'],
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(5),
            ],
            [
                'template_id' => Str::uuid(),
                'org_id' => $this->orgIds['FashionHub Retail'],
                'name' => 'Fashion Enthusiasts 18-35',
                'description' => 'Young professionals interested in fashion and lifestyle',
                'targeting_criteria' => json_encode([
                    'age_range' => ['min' => 18, 'max' => 35],
                    'gender' => ['female'],
                    'interests' => ['Fashion', 'Shopping', 'Style', 'Beauty'],
                    'behaviors' => ['Online Shoppers', 'Fashion Forward'],
                    'locations' => ['Urban areas', 'Major cities']
                ]),
                'platforms' => json_encode(['instagram', 'facebook', 'tiktok']),
                'usage_count' => 8,
                'last_used_at' => now()->subDays(2),
                'created_by' => $this->userIds['emma@fashionhub.com'],
                'created_at' => now()->subDays(45),
                'updated_at' => now()->subDays(2),
            ],
        ];

        foreach ($templates as $template) {
            DB::table('cmis.audience_templates')->insert($template);
        }
    }

    private function createNotifications()
    {
        foreach ($this->userIds as $email => $userId) {
            if ($email === 'admin@cmis.test') continue;

            // Get user's organizations
            $userOrgs = DB::table('cmis.user_orgs')
                ->where('user_id', $userId)
                ->get();

            foreach ($userOrgs as $userOrg) {
                // Create welcome notification
                DB::table('cmis.notifications')->insert([
                    'notification_id' => Str::uuid(),
                    'user_id' => $userId,
                    'org_id' => $userOrg->org_id,
                    'type' => 'welcome',
                    'title' => 'Welcome to CMIS!',
                    'message' => 'You have been added to the organization. Explore campaigns and start creating content.',
                    'data' => json_encode([
                        'action_url' => '/dashboard',
                        'icon' => 'welcome'
                    ]),
                    'read' => false,
                    'read_at' => null,
                    'created_at' => $userOrg->joined_at,
                    'updated_at' => $userOrg->joined_at,
                ]);

                // Create campaign notification
                $campaigns = DB::table('cmis.campaigns')
                    ->where('org_id', $userOrg->org_id)
                    ->get();

                foreach ($campaigns as $campaign) {
                    DB::table('cmis.notifications')->insert([
                        'notification_id' => Str::uuid(),
                        'user_id' => $userId,
                        'org_id' => $userOrg->org_id,
                        'type' => 'campaign',
                        'title' => 'Campaign Performance Update',
                        'message' => "Campaign '{$campaign->name}' has reached 50% of budget.",
                        'data' => json_encode([
                            'campaign_id' => $campaign->campaign_id,
                            'action_url' => "/campaigns/{$campaign->campaign_id}",
                            'icon' => 'chart'
                        ]),
                        'read' => rand(0, 1) === 1,
                        'read_at' => rand(0, 1) === 1 ? now()->subDays(rand(1, 5)) : null,
                        'created_at' => now()->subDays(rand(1, 10)),
                        'updated_at' => now()->subDays(rand(1, 10)),
                    ]);
                }
            }
        }
    }

    private function createUserActivities()
    {
        foreach ($this->userIds as $email => $userId) {
            $userOrgs = DB::table('cmis.user_orgs')
                ->where('user_id', $userId)
                ->get();

            foreach ($userOrgs as $userOrg) {
                // Create various activity types
                $activities = [
                    ['action' => 'login', 'entity_type' => null, 'entity_id' => null, 'details' => json_encode(['ip' => '192.168.1.100'])],
                    ['action' => 'view_campaign', 'entity_type' => 'campaign', 'entity_id' => null, 'details' => json_encode(['campaign_name' => 'Sample Campaign'])],
                    ['action' => 'create_post', 'entity_type' => 'social_post', 'entity_id' => null, 'details' => json_encode(['platform' => 'instagram'])],
                    ['action' => 'edit_creative', 'entity_type' => 'creative_asset', 'entity_id' => null, 'details' => json_encode(['changes' => 'Updated copy'])],
                ];

                foreach ($activities as $activity) {
                    DB::table('cmis.user_activities')->insert([
                        'activity_id' => Str::uuid(),
                        'user_id' => $userId,
                        'org_id' => $userOrg->org_id,
                        'session_id' => null,
                        'action' => $activity['action'],
                        'entity_type' => $activity['entity_type'],
                        'entity_id' => $activity['entity_id'],
                        'details' => $activity['details'],
                        'ip_address' => '192.168.1.' . rand(1, 255),
                        'created_at' => now()->subDays(rand(1, 30)),
                        'deleted_at' => null,
                        'provider' => null,
                    ]);
                }
            }
        }
    }

    private function createTeamInvitations()
    {
        // Create some pending invitations
        $invitations = [
            [
                'invitation_id' => Str::uuid(),
                'org_id' => $this->orgIds['TechVision Solutions'],
                'invited_email' => 'john.doe@techvision.com',
                'role_id' => $this->roleIds['analyst'],
                'invited_by' => $this->userIds['sarah@techvision.com'],
                'status' => 'pending',
                'sent_at' => now()->subDays(3),
                'accepted_at' => null,
                'expires_at' => now()->addDays(4),
            ],
            [
                'invitation_id' => Str::uuid(),
                'org_id' => $this->orgIds['FashionHub Retail'],
                'invited_email' => 'lisa.chen@fashionhub.com',
                'role_id' => $this->roleIds['content_creator'],
                'invited_by' => $this->userIds['emma@fashionhub.com'],
                'status' => 'pending',
                'sent_at' => now()->subDays(1),
                'accepted_at' => null,
                'expires_at' => now()->addDays(6),
            ],
        ];

        foreach ($invitations as $invitation) {
            DB::table('cmis.team_invitations')->insert($invitation);
        }
    }
}
