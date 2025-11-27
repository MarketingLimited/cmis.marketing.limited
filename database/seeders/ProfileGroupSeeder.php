<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfileGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing orgs and users
        $orgs = DB::table('cmis.orgs')->get();
        $users = DB::table('cmis.users')->get();

        if ($orgs->isEmpty() || $users->isEmpty()) {
            $this->command->error('No organizations or users found. Please run OrgSeeder and UserSeeder first.');
            return;
        }

        $this->command->info('Starting ProfileGroup seeding...');

        foreach ($orgs as $org) {
            // Set RLS context for this org
            DB::statement("SET app.current_org_id = '{$org->org_id}'");

            // Get a user from this org (or use first user as fallback)
            $creator = $users->first();

            $this->command->info("Seeding data for org: {$org->name}");

            // Create Brand Voices
            $brandVoiceIds = $this->createBrandVoices($org, $creator);

            // Create Brand Safety Policies
            $brandSafetyPolicyIds = $this->createBrandSafetyPolicies($org, $creator);

            // Create Profile Groups
            $profileGroupIds = $this->createProfileGroups($org, $creator, $brandVoiceIds, $brandSafetyPolicyIds);

            // Create Profile Group Members
            $this->createProfileGroupMembers($profileGroupIds, $users, $creator);

            // Create Ad Accounts
            $adAccountIds = $this->createAdAccounts($org, $profileGroupIds, $creator);

            // Create Approval Workflows
            $this->createApprovalWorkflows($org, $profileGroupIds, $creator);

            // Create Boost Rules
            $this->createBoostRules($org, $profileGroupIds, $adAccountIds, $creator);
        }

        // Reset RLS context
        DB::statement("RESET app.current_org_id");

        $this->command->info('ProfileGroup seeding completed successfully!');
    }

    private function createBrandVoices($org, $creator): array
    {
        $this->command->info('  - Creating brand voices...');

        $voices = [
            [
                'voice_id' => Str::uuid()->toString(),
                'org_id' => $org->org_id,
                'profile_group_id' => null, // Org-wide
                'name' => 'Professional & Authoritative',
                'description' => 'Expert voice for B2B communication, thought leadership, and industry insights.',
                'tone' => 'professional',
                'personality_traits' => json_encode(['knowledgeable', 'confident', 'trustworthy', 'precise']),
                'inspired_by' => json_encode(['Harvard Business Review', 'McKinsey Insights']),
                'target_audience' => 'C-level executives, business professionals, industry decision-makers',
                'keywords_to_use' => json_encode(['innovation', 'transformation', 'strategy', 'excellence', 'leadership']),
                'keywords_to_avoid' => json_encode(['cheap', 'discount', 'hurry', 'slang']),
                'emojis_preference' => 'minimal',
                'hashtag_strategy' => 'minimal',
                'example_posts' => json_encode([
                    'The future of digital transformation lies in strategic AI integration.',
                    'New research reveals key insights into customer behavior patterns.'
                ]),
                'primary_language' => 'en',
                'secondary_languages' => json_encode(['ar']),
                'dialect_preference' => 'US English',
                'ai_system_prompt' => 'You are a professional business communication expert. Write with authority, clarity, and insight.',
                'temperature' => 0.60,
                'created_by' => $creator->user_id,
            ],
            [
                'voice_id' => Str::uuid()->toString(),
                'org_id' => $org->org_id,
                'profile_group_id' => null,
                'name' => 'Friendly & Conversational',
                'description' => 'Warm, approachable voice for engaging with community and building relationships.',
                'tone' => 'friendly',
                'personality_traits' => json_encode(['warm', 'approachable', 'enthusiastic', 'helpful']),
                'inspired_by' => json_encode(['Mailchimp', 'Slack']),
                'target_audience' => 'General audience, community members, customers',
                'keywords_to_use' => json_encode(['community', 'together', 'excited', 'awesome', 'amazing']),
                'keywords_to_avoid' => json_encode(['corporate jargon', 'complicated terms']),
                'emojis_preference' => 'moderate',
                'hashtag_strategy' => 'moderate',
                'example_posts' => json_encode([
                    'Hey everyone! 👋 We\'re thrilled to share some exciting news with you all!',
                    'Big thanks to our amazing community for your continued support! ❤️'
                ]),
                'primary_language' => 'en',
                'secondary_languages' => json_encode(['ar']),
                'dialect_preference' => null,
                'ai_system_prompt' => 'You are a friendly community manager. Write in a warm, conversational tone that makes people feel welcomed.',
                'temperature' => 0.75,
                'created_by' => $creator->user_id,
            ],
            [
                'voice_id' => Str::uuid()->toString(),
                'org_id' => $org->org_id,
                'profile_group_id' => null,
                'name' => 'Arabic Modern & Engaging',
                'description' => 'Modern Arabic voice for engaging Middle Eastern audiences with cultural relevance.',
                'tone' => 'friendly',
                'personality_traits' => json_encode(['cultural', 'modern', 'respectful', 'engaging']),
                'inspired_by' => json_encode(['Regional brands', 'Arabic social media influencers']),
                'target_audience' => 'Arabic-speaking audiences in MENA region',
                'keywords_to_use' => json_encode(['إبداع', 'تميز', 'نجاح', 'مجتمع', 'تطور']),
                'keywords_to_avoid' => json_encode(['مصطلحات إنجليزية معقدة']),
                'emojis_preference' => 'generous',
                'hashtag_strategy' => 'generous',
                'example_posts' => json_encode([
                    'نحن فخورون بالإعلان عن إطلاق منتجنا الجديد! 🎉',
                    'شكراً لمجتمعنا الرائع على دعمكم المستمر ❤️'
                ]),
                'primary_language' => 'ar',
                'secondary_languages' => json_encode(['en']),
                'dialect_preference' => 'Modern Standard Arabic',
                'ai_system_prompt' => 'أنت مدير محتوى عربي محترف. اكتب بأسلوب حديث وجذاب يناسب الجمهور العربي.',
                'temperature' => 0.70,
                'created_by' => $creator->user_id,
            ],
        ];

        $voiceIds = [];
        foreach ($voices as $voice) {
            DB::table('cmis.brand_voices')->insert($voice);
            $voiceIds[] = $voice['voice_id'];
        }

        return $voiceIds;
    }

    private function createBrandSafetyPolicies($org, $creator): array
    {
        $this->command->info('  - Creating brand safety policies...');

        $policies = [
            [
                'policy_id' => Str::uuid()->toString(),
                'org_id' => $org->org_id,
                'profile_group_id' => null, // Org-wide default
                'name' => 'Standard Brand Safety',
                'description' => 'Default brand safety policy for general content',
                'is_active' => true,
                'prohibit_derogatory_language' => true,
                'prohibit_profanity' => true,
                'prohibit_offensive_content' => true,
                'custom_banned_words' => json_encode(['spam', 'scam', 'fake']),
                'custom_banned_phrases' => json_encode([]),
                'custom_requirements' => 'All content must align with brand values and maintain professional standards.',
                'require_disclosure' => false,
                'disclosure_text' => null,
                'require_fact_checking' => false,
                'require_source_citation' => false,
                'industry_regulations' => json_encode([]),
                'compliance_regions' => json_encode(['US-FTC', 'EU-GDPR']),
                'enforcement_level' => 'warning',
                'auto_reject_violations' => false,
                'use_default_template' => true,
                'template_name' => 'standard',
                'created_by' => $creator->user_id,
            ],
            [
                'policy_id' => Str::uuid()->toString(),
                'org_id' => $org->org_id,
                'profile_group_id' => null,
                'name' => 'Strict Compliance Policy',
                'description' => 'Strict brand safety for regulated industries',
                'is_active' => true,
                'prohibit_derogatory_language' => true,
                'prohibit_profanity' => true,
                'prohibit_offensive_content' => true,
                'custom_banned_words' => json_encode(['guarantee', 'promise', 'cure', 'miracle']),
                'custom_banned_phrases' => json_encode(['risk-free', '100% guaranteed']),
                'custom_requirements' => 'All claims must be substantiated. Medical/financial content requires legal review.',
                'require_disclosure' => true,
                'disclosure_text' => '#ad #sponsored',
                'require_fact_checking' => true,
                'require_source_citation' => true,
                'industry_regulations' => json_encode(['HIPAA', 'Financial Services']),
                'compliance_regions' => json_encode(['US-FTC', 'EU-GDPR', 'UK-ASA']),
                'enforcement_level' => 'block',
                'auto_reject_violations' => true,
                'use_default_template' => false,
                'template_name' => 'strict',
                'created_by' => $creator->user_id,
            ],
        ];

        $policyIds = [];
        foreach ($policies as $policy) {
            DB::table('cmis.brand_safety_policies')->insert($policy);
            $policyIds[] = $policy['policy_id'];
        }

        return $policyIds;
    }

    private function createProfileGroups($org, $creator, $brandVoiceIds, $brandSafetyPolicyIds): array
    {
        $this->command->info('  - Creating profile groups...');

        $groups = [
            [
                'group_id' => Str::uuid()->toString(),
                'org_id' => $org->org_id,
                'name' => 'Main Brand Accounts',
                'description' => 'Primary social media accounts for the main brand',
                'client_location' => json_encode(['country' => 'United States', 'city' => 'New York']),
                'logo_url' => 'https://example.com/logo-main.png',
                'color' => '#3B82F6',
                'default_link_shortener' => 'bitly',
                'timezone' => 'America/New_York',
                'language' => 'en',
                'brand_voice_id' => $brandVoiceIds[0],
                'brand_safety_policy_id' => $brandSafetyPolicyIds[0],
                'created_by' => $creator->user_id,
            ],
            [
                'group_id' => Str::uuid()->toString(),
                'org_id' => $org->org_id,
                'name' => 'Regional MENA Accounts',
                'description' => 'Social accounts for Middle East and North Africa market',
                'client_location' => json_encode(['country' => 'United Arab Emirates', 'city' => 'Dubai']),
                'logo_url' => 'https://example.com/logo-mena.png',
                'color' => '#10B981',
                'default_link_shortener' => 'bitly',
                'timezone' => 'Asia/Dubai',
                'language' => 'ar',
                'brand_voice_id' => $brandVoiceIds[2],
                'brand_safety_policy_id' => $brandSafetyPolicyIds[0],
                'created_by' => $creator->user_id,
            ],
            [
                'group_id' => Str::uuid()->toString(),
                'org_id' => $org->org_id,
                'name' => 'Community & Support',
                'description' => 'Customer support and community engagement accounts',
                'client_location' => json_encode(['country' => 'United States', 'city' => 'San Francisco']),
                'logo_url' => 'https://example.com/logo-support.png',
                'color' => '#8B5CF6',
                'default_link_shortener' => 'bitly',
                'timezone' => 'America/Los_Angeles',
                'language' => 'en',
                'brand_voice_id' => $brandVoiceIds[1],
                'brand_safety_policy_id' => $brandSafetyPolicyIds[1],
                'created_by' => $creator->user_id,
            ],
        ];

        $groupIds = [];
        foreach ($groups as $group) {
            DB::table('cmis.profile_groups')->insert($group);
            $groupIds[] = $group['group_id'];
        }

        return $groupIds;
    }

    private function createProfileGroupMembers($profileGroupIds, $users, $creator): void
    {
        $this->command->info('  - Creating profile group members...');

        foreach ($profileGroupIds as $groupId) {
            // Add creator as owner
            DB::table('cmis.profile_group_members')->insert([
                'id' => Str::uuid()->toString(),
                'profile_group_id' => $groupId,
                'user_id' => $creator->user_id,
                'role' => 'owner',
                'permissions' => json_encode([
                    'can_publish' => true,
                    'can_schedule' => true,
                    'can_edit_drafts' => true,
                    'can_delete' => true,
                    'can_manage_team' => true,
                    'can_manage_brand_voice' => true,
                    'can_manage_ad_accounts' => true,
                    'requires_approval' => false,
                ]),
                'assigned_by' => $creator->user_id,
                'joined_at' => now(),
                'last_active_at' => now(),
            ]);

            // Add other users as contributors if there are multiple users
            foreach ($users as $user) {
                if ($user->user_id !== $creator->user_id) {
                    DB::table('cmis.profile_group_members')->insert([
                        'id' => Str::uuid()->toString(),
                        'profile_group_id' => $groupId,
                        'user_id' => $user->user_id,
                        'role' => 'contributor',
                        'permissions' => json_encode([
                            'can_publish' => false,
                            'can_schedule' => true,
                            'can_edit_drafts' => true,
                            'can_delete' => false,
                            'can_manage_team' => false,
                            'can_manage_brand_voice' => false,
                            'can_manage_ad_accounts' => false,
                            'requires_approval' => true,
                        ]),
                        'assigned_by' => $creator->user_id,
                        'joined_at' => now(),
                        'last_active_at' => now(),
                    ]);
                    break; // Only add one additional member per group
                }
            }
        }
    }

    private function createAdAccounts($org, $profileGroupIds, $creator): array
    {
        $this->command->info('  - Creating ad accounts...');

        // Check if ad accounts already exist
        $existingAccounts = DB::table('cmis.ad_accounts')
            ->where('org_id', $org->org_id)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();

        if (!empty($existingAccounts)) {
            $this->command->info('    Ad accounts already exist, using existing');
            return $existingAccounts;
        }

        $adAccounts = [
            [
                'id' => Str::uuid()->toString(),
                'org_id' => $org->org_id,
                'profile_group_id' => $profileGroupIds[0] ?? null,
                'platform' => 'meta',
                'platform_account_id' => 'act_' . rand(100000000, 999999999),
                'account_name' => 'Meta Ads Account - Main',
                'currency' => 'USD',
                'timezone' => 'America/New_York',
                'status' => 'active',
                'connection_status' => 'connected',
                'balance' => 1000.00,
                'daily_spend_limit' => 500.00,
                'connected_by' => $creator->user_id,
                'connected_at' => now(),
                'last_synced_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'org_id' => $org->org_id,
                'profile_group_id' => $profileGroupIds[1] ?? $profileGroupIds[0] ?? null,
                'platform' => 'google',
                'platform_account_id' => rand(1000000000, 9999999999),
                'account_name' => 'Google Ads Account',
                'currency' => 'USD',
                'timezone' => 'America/Los_Angeles',
                'status' => 'active',
                'connection_status' => 'connected',
                'balance' => 2500.00,
                'daily_spend_limit' => 1000.00,
                'connected_by' => $creator->user_id,
                'connected_at' => now(),
                'last_synced_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $accountIds = [];
        foreach ($adAccounts as $account) {
            try {
                DB::table('cmis.ad_accounts')->insert($account);
                $accountIds[] = $account['id'];
            } catch (\Exception $e) {
                $this->command->warn("    Failed to create ad account: " . $e->getMessage());
            }
        }

        return $accountIds;
    }

    private function createApprovalWorkflows($org, $profileGroupIds, $creator): void
    {
        $this->command->info('  - Creating approval workflows...');

        foreach ($profileGroupIds as $index => $groupId) {
            if ($index < 2) { // Only create workflows for first 2 groups
                DB::table('cmis.approval_workflows')->insert([
                    'workflow_id' => Str::uuid()->toString(),
                    'org_id' => $org->org_id,
                    'profile_group_id' => $groupId,
                    'name' => 'Standard Approval Process',
                    'description' => 'Default two-step approval workflow',
                    'is_active' => true,
                    'apply_to_platforms' => json_encode(['meta', 'twitter', 'linkedin']),
                    'apply_to_users' => json_encode([]), // Apply to all users
                    'apply_to_post_types' => json_encode(['post', 'story']),
                    'approval_steps' => json_encode([
                        [
                            'step' => 1,
                            'approver_role' => 'admin',
                            'required' => true,
                        ],
                        [
                            'step' => 2,
                            'approver_role' => 'owner',
                            'required' => false,
                        ],
                    ]),
                    'notify_on_submission' => true,
                    'notify_on_approval' => true,
                    'notify_on_rejection' => true,
                    'created_by' => $creator->user_id,
                ]);
            }
        }
    }

    private function createBoostRules($org, $profileGroupIds, $adAccountIds, $creator): void
    {
        $this->command->info('  - Creating boost rules...');

        if (empty($adAccountIds)) {
            $this->command->warn('    No ad accounts available, skipping boost rules');
            return;
        }

        foreach ($profileGroupIds as $index => $groupId) {
            if ($index < 2) { // Only create boost rules for first 2 groups
                // Manual trigger boost rule
                DB::table('cmis.boost_rules')->insert([
                    'boost_rule_id' => Str::uuid()->toString(),
                    'org_id' => $org->org_id,
                    'profile_group_id' => $groupId,
                    'name' => 'Manual Boost on Demand',
                    'description' => 'Boost posts manually when needed',
                    'is_active' => true,
                    'trigger_type' => 'manual',
                    'delay_after_publish' => null,
                    'performance_threshold' => null,
                    'apply_to_social_profiles' => json_encode([]),
                    'ad_account_id' => $adAccountIds[0],
                    'boost_config' => json_encode([
                        'objective' => 'engagement',
                        'budget_amount' => 50.00,
                        'budget_type' => 'daily',
                        'duration_days' => 3,
                        'audience' => [
                            'age_min' => 18,
                            'age_max' => 65,
                            'locations' => ['US'],
                        ],
                    ]),
                    'created_by' => $creator->user_id,
                ]);

                // Auto-performance boost rule
                DB::table('cmis.boost_rules')->insert([
                    'boost_rule_id' => Str::uuid()->toString(),
                    'org_id' => $org->org_id,
                    'profile_group_id' => $groupId,
                    'name' => 'Auto-Boost High Performers',
                    'description' => 'Automatically boost posts with high engagement',
                    'is_active' => true,
                    'trigger_type' => 'auto_performance',
                    'delay_after_publish' => null,
                    'performance_threshold' => json_encode([
                        'metric' => 'engagement_rate',
                        'operator' => '>',
                        'value' => 5.0,
                        'time_window_hours' => 24,
                    ]),
                    'apply_to_social_profiles' => json_encode([]),
                    'ad_account_id' => $adAccountIds[0],
                    'boost_config' => json_encode([
                        'objective' => 'reach',
                        'budget_amount' => 100.00,
                        'budget_type' => 'lifetime',
                        'duration_days' => 5,
                        'audience' => [
                            'age_min' => 18,
                            'age_max' => 65,
                            'locations' => ['US', 'CA', 'GB'],
                        ],
                    ]),
                    'created_by' => $creator->user_id,
                ]);
            }
        }
    }
}
