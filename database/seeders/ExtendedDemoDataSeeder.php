<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Extended demo data seeder covering ALL remaining major tables
 * This seeds 50+ additional tables to provide complete application coverage
 */
class ExtendedDemoDataSeeder extends Seeder
{
    private $orgIds;
    private $userIds;
    private $campaignIds;
    private $channelIds;
    private $industryIds;
    private $marketIds;

    public function run(): void
    {
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        $this->loadReferenceData();

        $this->command->info('📦 Seeding extended demo data...');

        // System & Configuration
        $this->createModules();
        $this->createFieldDefinitions();
        $this->createPromptTemplates();
        $this->createReferenceEntities();
        $this->createComponentTypes();
        $this->createProofLayers();
        $this->createNamingTemplates();
        $this->createOutputContracts();
        $this->createVariationPolicies();

        // Contexts & Value Propositions
        $this->createContextsBase();
        $this->createValueContexts();
        $this->createFieldValues();
        $this->createCampaignContextLinks();

        // Creative Components
        $this->createCopyComponents();
        $this->createVideoTemplates();
        $this->createAudioTemplates();
        $this->createSceneLibrary();
        $this->createVideoScenes();

        // AI & Knowledge
        $this->createAIModels();
        $this->createAIActions();
        $this->createAIGeneratedCampaigns();
        $this->createCognitiveTrends();
        $this->createPredictiveVisualEngine();

        // Compliance & Quality
        $this->createComplianceRules();
        $this->createComplianceAudits();

        // Experiments
        $this->createExperiments();
        $this->createExportBundles();

        // Data Management
        $this->createDatasetPackages();
        $this->createDataFeeds();

        // Analytics & Operations
        $this->createAnalyticsIntegrations();
        $this->createOpsAudit();
        $this->createSyncLogs();
        $this->createScheduledReports();

        // Ad Platform Extensions
        $this->createAdAudiences();
        $this->createAdMetrics();
        $this->createAdVariants();
        $this->createSocialMetrics();

        // Automation
        $this->createSQLSnippets();
        $this->createFlows();

        // Metadata
        $this->createMetaDocumentation();

        $this->command->info('✓ Extended demo data seeded successfully!');
    }

    private function loadReferenceData()
    {
        $this->orgIds = DB::table('cmis.orgs')->pluck('org_id', 'name')->toArray();
        $this->userIds = DB::table('cmis.users')->pluck('user_id', 'email')->toArray();
        $this->campaignIds = DB::table('cmis.campaigns')->pluck('campaign_id')->toArray();
        $this->channelIds = DB::table('public.channels')->pluck('channel_id', 'code')->toArray();
        $this->industryIds = DB::table('public.industries')->pluck('industry_id', 'name')->toArray();
        $this->marketIds = DB::table('public.markets')->pluck('market_id', 'market_name')->toArray();
    }

    private function createModules()
    {
        $modules = [
            ['code' => 'campaign', 'name' => 'Campaign Management', 'version' => '1.0.0'],
            ['code' => 'creative', 'name' => 'Creative Assets', 'version' => '1.0.0'],
            ['code' => 'social', 'name' => 'Social Media', 'version' => '1.0.0'],
            ['code' => 'ads', 'name' => 'Advertising', 'version' => '1.0.0'],
            ['code' => 'analytics', 'name' => 'Analytics & Reporting', 'version' => '1.0.0'],
        ];

        foreach ($modules as $module) {
            DB::table('cmis.modules')->insert([
                'module_id' => Str::uuid(),
                'code' => $module['code'],
                'name' => $module['name'],
                'version' => $module['version'],
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createFieldDefinitions()
    {
        $modules = DB::table('cmis.modules')->get();

        foreach ($modules as $module) {
            $fields = $this->getFieldsForModule($module->code);

            foreach ($fields as $field) {
                DB::table('cmis.field_definitions')->insert([
                    'field_id' => Str::uuid(),
                    'module_id' => $module->module_id,
                    'name' => $field['name'],
                    'slug' => $field['slug'],
                    'data_type' => $field['data_type'],
                    'is_list' => $field['is_list'] ?? false,
                    'description' => $field['description'],
                    'enum_options' => isset($field['options']) ? json_encode($field['options']) : null,
                    'required_default' => $field['required'] ?? false,
                    'guidance_anchor' => null,
                    'validations' => json_encode($field['validations'] ?? []),
                    'module_scope' => $module->code,
                    'created_at' => now(),
                    'deleted_at' => null,
                    'provider' => null,
                ]);
            }
        }
    }

    private function getFieldsForModule($moduleCode)
    {
        $fieldSets = [
            'campaign' => [
                ['name' => 'Campaign Name', 'slug' => 'campaign_name', 'data_type' => 'string', 'description' => 'Name of the campaign', 'required' => true, 'validations' => ['max_length' => 255]],
                ['name' => 'Objective', 'slug' => 'objective', 'data_type' => 'enum', 'description' => 'Campaign objective', 'options' => ['awareness', 'conversion', 'engagement'], 'required' => true],
                ['name' => 'Budget', 'slug' => 'budget', 'data_type' => 'decimal', 'description' => 'Campaign budget', 'required' => true, 'validations' => ['min' => 0]],
                ['name' => 'Target Audience', 'slug' => 'target_audience', 'data_type' => 'text', 'description' => 'Description of target audience', 'required' => false],
            ],
            'creative' => [
                ['name' => 'Headline', 'slug' => 'headline', 'data_type' => 'string', 'description' => 'Main headline', 'required' => true, 'validations' => ['max_length' => 100]],
                ['name' => 'Body Copy', 'slug' => 'body_copy', 'data_type' => 'text', 'description' => 'Main body text', 'required' => true],
                ['name' => 'Call to Action', 'slug' => 'cta', 'data_type' => 'string', 'description' => 'CTA button text', 'required' => true, 'validations' => ['max_length' => 30]],
                ['name' => 'Brand Voice', 'slug' => 'brand_voice', 'data_type' => 'enum', 'description' => 'Tone of voice', 'options' => ['professional', 'friendly', 'casual', 'formal'], 'required' => false],
            ],
            'social' => [
                ['name' => 'Platform', 'slug' => 'platform', 'data_type' => 'enum', 'description' => 'Social platform', 'options' => ['facebook', 'instagram', 'twitter', 'linkedin'], 'required' => true],
                ['name' => 'Post Type', 'slug' => 'post_type', 'data_type' => 'enum', 'description' => 'Type of post', 'options' => ['image', 'video', 'carousel', 'story'], 'required' => true],
                ['name' => 'Hashtags', 'slug' => 'hashtags', 'data_type' => 'string', 'description' => 'Post hashtags', 'is_list' => true, 'required' => false],
            ],
        ];

        return $fieldSets[$moduleCode] ?? [];
    }

    private function createPromptTemplates()
    {
        $modules = DB::table('cmis.modules')->get();

        foreach ($modules as $module) {
            DB::table('cmis.prompt_templates')->insert([
                'prompt_id' => Str::uuid(),
                'module_id' => $module->module_id,
                'name' => "Generate {$module->name} Content",
                'task' => 'content_generation',
                'instructions' => "Generate high-quality content for {$module->name} based on provided context and requirements.",
                'version' => '1.0.0',
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createReferenceEntities()
    {
        $categories = [
            ['category' => 'channel_type', 'code' => 'social_media', 'label' => 'Social Media', 'description' => 'Social media platforms'],
            ['category' => 'channel_type', 'code' => 'paid_search', 'label' => 'Paid Search', 'description' => 'Search engine advertising'],
            ['category' => 'channel_type', 'code' => 'display', 'label' => 'Display Advertising', 'description' => 'Banner and display ads'],
            ['category' => 'content_type', 'code' => 'blog', 'label' => 'Blog Post', 'description' => 'Written blog content'],
            ['category' => 'content_type', 'code' => 'video', 'label' => 'Video', 'description' => 'Video content'],
            ['category' => 'content_type', 'code' => 'infographic', 'label' => 'Infographic', 'description' => 'Visual infographic'],
        ];

        foreach ($categories as $entity) {
            DB::table('cmis.reference_entities')->insert([
                'ref_id' => Str::uuid(),
                'category' => $entity['category'],
                'code' => $entity['code'],
                'label' => $entity['label'],
                'description' => $entity['description'],
                'metadata' => json_encode(['active' => true]),
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createComponentTypes()
    {
        $types = [
            ['code' => 'headline', 'name' => 'Headline', 'max_length' => 100],
            ['code' => 'subheadline', 'name' => 'Subheadline', 'max_length' => 150],
            ['code' => 'body_copy', 'name' => 'Body Copy', 'max_length' => 500],
            ['code' => 'cta', 'name' => 'Call to Action', 'max_length' => 30],
            ['code' => 'description', 'name' => 'Description', 'max_length' => 250],
        ];

        foreach ($types as $type) {
            DB::table('public.component_types')->insert([
                'type_code' => $type['code'],
                'display_name' => $type['name'],
                'constraints' => json_encode(['max_length' => $type['max_length']]),
            ]);
        }
    }

    private function createProofLayers()
    {
        $layers = [
            ['layer' => 'authority', 'description' => 'Establish authority and credibility'],
            ['layer' => 'social_proof', 'description' => 'Leverage social validation'],
            ['layer' => 'data', 'description' => 'Support with data and statistics'],
            ['layer' => 'testimonial', 'description' => 'Customer testimonials'],
        ];

        foreach ($layers as $layer) {
            DB::table('public.proof_layers')->insert($layer);
        }
    }

    private function createNamingTemplates()
    {
        $templates = [
            ['scope' => 'campaign', 'template' => '{objective}_{product}_{market}_{date}'],
            ['scope' => 'adset', 'template' => '{audience}_{placement}_{budget}'],
            ['scope' => 'creative', 'template' => '{format}_{variation}_{version}'],
        ];

        foreach ($templates as $template) {
            DB::table('cmis.naming_templates')->insert([
                'naming_id' => Str::uuid(),
                'scope' => $template['scope'],
                'template' => $template['template'],
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createOutputContracts()
    {
        $contracts = [
            [
                'code' => 'campaign_brief',
                'json_schema' => json_encode([
                    'type' => 'object',
                    'required' => ['objective', 'target_audience', 'budget'],
                    'properties' => [
                        'objective' => ['type' => 'string'],
                        'target_audience' => ['type' => 'string'],
                        'budget' => ['type' => 'number'],
                        'kpis' => ['type' => 'array'],
                    ]
                ]),
                'notes' => 'Schema for campaign brief output',
            ],
            [
                'code' => 'creative_asset',
                'json_schema' => json_encode([
                    'type' => 'object',
                    'required' => ['headline', 'body', 'cta'],
                    'properties' => [
                        'headline' => ['type' => 'string', 'maxLength' => 100],
                        'body' => ['type' => 'string'],
                        'cta' => ['type' => 'string', 'maxLength' => 30],
                    ]
                ]),
                'notes' => 'Schema for creative asset output',
            ],
        ];

        foreach ($contracts as $contract) {
            DB::table('cmis.output_contracts')->insert([
                'contract_id' => Str::uuid(),
                'code' => $contract['code'],
                'json_schema' => $contract['json_schema'],
                'notes' => $contract['notes'],
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createVariationPolicies()
    {
        foreach ($this->orgIds as $orgName => $orgId) {
            DB::table('cmis.variation_policies')->insert([
                'policy_id' => Str::uuid(),
                'org_id' => $orgId,
                'max_variations' => 5,
                'dco_enabled' => true,
                'naming_ref' => 'variation_{index}',
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createContextsBase()
    {
        foreach ($this->orgIds as $orgName => $orgId) {
            $contexts = [
                ['type' => 'creative', 'name' => "$orgName Creative Context"],
                ['type' => 'value', 'name' => "$orgName Value Proposition"],
                ['type' => 'offering', 'name' => "$orgName Product Context"],
            ];

            foreach ($contexts as $context) {
                DB::table('cmis.contexts_base')->insert([
                    'id' => Str::uuid(),
                    'context_type' => $context['type'],
                    'name' => $context['name'],
                    'org_id' => $orgId,
                    'created_at' => now(),
                    'deleted_at' => null,
                    'provider' => null,
                ]);
            }
        }
    }

    private function createValueContexts()
    {
        if (empty($this->campaignIds)) return;

        foreach ($this->campaignIds as $campaignId) {
            $campaign = DB::table('cmis.campaigns')->where('campaign_id', $campaignId)->first();
            if (!$campaign) continue;

            DB::table('cmis.value_contexts')->insert([
                'context_id' => Str::uuid(),
                'org_id' => $campaign->org_id,
                'offering_id' => null,
                'segment_id' => null,
                'campaign_id' => $campaignId,
                'channel_id' => $this->channelIds['instagram'] ?? null,
                'format_id' => null,
                'locale' => 'en',
                'awareness_stage' => 'problem_aware',
                'funnel_stage' => 'middle_of_funnel',
                'framework' => 'AIDA',
                'tone' => 'professional',
                'dataset_ref' => null,
                'variant_tag' => 'A',
                'tags' => json_encode(['digital', 'b2b']),
                'market_id' => null,
                'industry_id' => null,
                'created_at' => now(),
                'context_fingerprint' => md5($campaignId . time()),
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createFieldValues()
    {
        $fields = DB::table('cmis.field_definitions')->limit(10)->get();
        $contexts = DB::table('cmis.value_contexts')->get();

        foreach ($contexts as $context) {
            foreach ($fields->take(3) as $field) {
                DB::table('cmis.field_values')->insert([
                    'value_id' => Str::uuid(),
                    'field_id' => $field->field_id,
                    'context_id' => $context->context_id,
                    'value' => 'Sample value for ' . $field->name,
                    'source' => 'manual',
                    'provider_ref' => null,
                    'justification' => 'Demo data',
                    'confidence' => 0.95,
                    'created_at' => now(),
                    'deleted_at' => null,
                    'provider' => null,
                ]);
            }
        }
    }

    private function createCampaignContextLinks()
    {
        $contexts = DB::table('cmis.value_contexts')->get();

        foreach ($contexts as $context) {
            if (!$context->campaign_id) continue;

            DB::table('cmis.campaign_context_links')->insert([
                'id' => Str::uuid(),
                'campaign_id' => $context->campaign_id,
                'context_id' => $context->context_id,
                'context_type' => 'value',
                'link_type' => 'primary',
                'link_strength' => 0.9,
                'link_purpose' => 'Main value proposition',
                'link_notes' => null,
                'effective_from' => now()->subDays(30),
                'effective_to' => null,
                'is_active' => true,
                'created_at' => now(),
                'created_by' => array_values($this->userIds)[0] ?? null,
                'updated_at' => now(),
                'updated_by' => null,
                'metadata' => json_encode(['auto_generated' => true]),
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createCopyComponents()
    {
        $channelId = $this->channelIds['instagram'] ?? null;

        $components = [
            ['type' => 'headline', 'content' => 'Transform Your Business Today', 'quality' => 0.9],
            ['type' => 'headline', 'content' => 'Discover the Future of Marketing', 'quality' => 0.85],
            ['type' => 'body_copy', 'content' => 'Join thousands of businesses already seeing results with our platform.', 'quality' => 0.88],
            ['type' => 'cta', 'content' => 'Get Started Free', 'quality' => 0.92],
            ['type' => 'cta', 'content' => 'Learn More', 'quality' => 0.87],
        ];

        foreach ($components as $component) {
            DB::table('cmis.copy_components')->insert([
                'component_id' => Str::uuid(),
                'type_code' => $component['type'],
                'content' => $component['content'],
                'industry_id' => array_values($this->industryIds)[0] ?? null,
                'market_id' => array_values($this->marketIds)[0] ?? null,
                'awareness_stage' => 'solution_aware',
                'channel_id' => $channelId,
                'usage_notes' => 'High-performing copy component',
                'quality_score' => $component['quality'],
                'created_at' => now(),
                'context_id' => null,
                'example_id' => null,
                'campaign_id' => null,
                'plan_id' => null,
                'visual_prompt' => null,
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createVideoTemplates()
    {
        foreach (array_slice($this->orgIds, 0, 2) as $orgName => $orgId) {
            DB::table('cmis.video_templates')->insert([
                'vtpl_id' => Str::uuid(),
                'org_id' => $orgId,
                'channel_id' => $this->channelIds['instagram'] ?? null,
                'format_id' => null,
                'name' => 'Instagram Reel Template',
                'steps' => json_encode([
                    ['step' => 1, 'duration' => 3, 'instruction' => 'Hook - attention grabber'],
                    ['step' => 2, 'duration' => 5, 'instruction' => 'Problem - state the problem'],
                    ['step' => 3, 'duration' => 7, 'instruction' => 'Solution - present solution'],
                    ['step' => 4, 'duration' => 3, 'instruction' => 'CTA - call to action'],
                ]),
                'version' => '1.0.0',
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createAudioTemplates()
    {
        foreach (array_slice($this->orgIds, 0, 2) as $orgName => $orgId) {
            DB::table('cmis.audio_templates')->insert([
                'atpl_id' => Str::uuid(),
                'org_id' => $orgId,
                'name' => 'Upbeat Marketing Audio',
                'voice_hints' => 'energetic, professional, clear',
                'sfx_pack' => 'corporate_upbeat',
                'version' => '1.0.0',
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createSceneLibrary()
    {
        foreach (array_slice($this->orgIds, 0, 2) as $orgName => $orgId) {
            DB::table('cmis.scene_library')->insert([
                'scene_id' => Str::uuid(),
                'org_id' => $orgId,
                'name' => 'Product Showcase',
                'goal' => 'Highlight product features and benefits',
                'duration_sec' => 5,
                'visual_spec' => json_encode(['style' => 'modern', 'lighting' => 'bright', 'angle' => 'close-up']),
                'audio_spec' => json_encode(['music' => 'upbeat', 'voiceover' => 'professional']),
                'overlay_rules' => json_encode(['text_position' => 'bottom_third', 'logo' => 'top_right']),
                'anchor' => 'product_features',
                'quality_score' => 0.89,
                'tags' => json_encode(['product', 'features', 'benefits']),
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createVideoScenes()
    {
        $assets = DB::table('cmis.creative_assets')->limit(2)->get();

        foreach ($assets as $asset) {
            for ($i = 1; $i <= 3; $i++) {
                DB::table('cmis.video_scenes')->insert([
                    'scene_id' => Str::uuid(),
                    'asset_id' => $asset->asset_id,
                    'scene_number' => $i,
                    'duration_seconds' => rand(3, 7),
                    'visual_prompt_en' => "Scene $i: Professional product shot",
                    'overlay_text_ar' => null,
                    'audio_instructions' => 'Upbeat background music, professional voiceover',
                    'technical_specs' => json_encode(['resolution' => '1080x1920', 'fps' => 30]),
                    'deleted_at' => null,
                    'provider' => null,
                ]);
            }
        }
    }

    private function createAIModels()
    {
        foreach (array_slice($this->orgIds, 0, 2) as $orgName => $orgId) {
            DB::table('cmis.ai_models')->insert([
                'model_id' => Str::uuid(),
                'org_id' => $orgId,
                'name' => 'Content Generation Model',
                'engine' => 'openai',
                'version' => '4.0',
                'model_name' => 'gpt-4',
                'model_family' => 'gpt',
                'description' => 'AI model for generating marketing content',
                'status' => 'active',
                'trained_at' => now()->subDays(30),
                'created_at' => now()->subDays(60),
                'deleted_at' => null,
                'provider' => 'openai',
            ]);
        }
    }

    private function createAIActions()
    {
        if (empty($this->campaignIds)) return;

        foreach (array_slice($this->campaignIds, 0, 2) as $campaignId) {
            $campaign = DB::table('cmis.campaigns')->where('campaign_id', $campaignId)->first();

            DB::table('cmis.ai_actions')->insert([
                'action_id' => Str::uuid(),
                'org_id' => $campaign->org_id,
                'campaign_id' => $campaignId,
                'prompt_used' => 'Generate campaign copy for product launch targeting enterprise customers',
                'sql_executed' => null,
                'result_summary' => 'Generated 5 headline variations and 3 body copy options',
                'confidence_score' => 0.92,
                'created_at' => now()->subDays(rand(1, 15)),
                'audit_id' => null,
                'deleted_at' => null,
                'provider' => 'openai',
            ]);
        }
    }

    private function createAIGeneratedCampaigns()
    {
        foreach (array_slice($this->campaignIds, 0, 1) as $campaignId) {
            $campaign = DB::table('cmis.campaigns')->where('campaign_id', $campaignId)->first();

            DB::table('cmis.ai_generated_campaigns')->insert([
                'campaign_id' => $campaignId,
                'org_id' => $campaign->org_id,
                'objective_code' => 'conversion',
                'recommended_principle' => 'AIDA',
                'linked_kpi' => 'conversion_rate',
                'ai_summary' => 'AI-generated campaign focusing on conversion optimization with targeted messaging',
                'ai_design_guideline' => 'Use clean, professional design with strong CTAs',
                'created_at' => now()->subDays(20),
                'engine' => 'gpt-4',
                'deleted_at' => null,
                'provider' => 'openai',
            ]);
        }
    }

    private function createCognitiveTrends()
    {
        foreach (array_slice($this->orgIds, 0, 2) as $orgName => $orgId) {
            DB::table('cmis.cognitive_trends')->insert([
                'trend_id' => Str::uuid(),
                'org_id' => $orgId,
                'factor_name' => 'Trust Signals',
                'trend_direction' => 'increasing',
                'growth_rate' => 15.5,
                'trend_strength' => 0.85,
                'summary_insight' => 'Trust signals showing strong positive trend in engagement',
                'created_at' => now()->subDays(rand(1, 30)),
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createPredictiveVisualEngine()
    {
        if (empty($this->campaignIds)) return;

        foreach (array_slice($this->campaignIds, 0, 2) as $campaignId) {
            $campaign = DB::table('cmis.campaigns')->where('campaign_id', $campaignId)->first();

            DB::table('cmis.predictive_visual_engine')->insert([
                'prediction_id' => Str::uuid(),
                'org_id' => $campaign->org_id,
                'campaign_id' => $campaignId,
                'predicted_ctr' => rand(2, 5) + (rand(0, 99) / 100),
                'predicted_engagement' => rand(3, 8) + (rand(0, 99) / 100),
                'predicted_trust_index' => rand(70, 95) / 100,
                'confidence_level' => rand(85, 95) / 100,
                'visual_factor_weight' => json_encode(['color' => 0.3, 'composition' => 0.4, 'imagery' => 0.3]),
                'prediction_summary' => 'High engagement predicted based on visual analysis',
                'created_at' => now()->subDays(rand(1, 10)),
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createComplianceRules()
    {
        $rules = [
            ['code' => 'text_length', 'description' => 'Text must not exceed maximum length', 'severity' => 'error', 'params' => json_encode(['max_length' => 280])],
            ['code' => 'prohibited_words', 'description' => 'Contains prohibited words', 'severity' => 'error', 'params' => json_encode(['words' => ['guaranteed', 'free money']])],
            ['code' => 'brand_consistency', 'description' => 'Must follow brand guidelines', 'severity' => 'warning', 'params' => json_encode(['check_colors' => true, 'check_fonts' => true])],
        ];

        foreach ($rules as $rule) {
            DB::table('cmis.compliance_rules')->insert([
                'rule_id' => Str::uuid(),
                'code' => $rule['code'],
                'description' => $rule['description'],
                'severity' => $rule['severity'],
                'params' => $rule['params'],
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createComplianceAudits()
    {
        $assets = DB::table('cmis.creative_assets')->get();
        $rules = DB::table('cmis.compliance_rules')->get();

        foreach ($assets as $asset) {
            foreach ($rules->take(2) as $rule) {
                DB::table('cmis.compliance_audits')->insert([
                    'audit_id' => Str::uuid(),
                    'asset_id' => $asset->asset_id,
                    'rule_id' => $rule->rule_id,
                    'status' => rand(0, 1) ? 'passed' : 'warning',
                    'owner' => array_values($this->userIds)[0] ?? null,
                    'notes' => 'Automated compliance check',
                    'created_at' => now()->subDays(rand(1, 15)),
                    'deleted_at' => null,
                    'provider' => null,
                ]);
            }
        }
    }

    private function createExperiments()
    {
        foreach (array_slice($this->orgIds, 0, 2) as $orgName => $orgId) {
            DB::table('cmis.experiments')->insert([
                'exp_id' => Str::uuid(),
                'org_id' => $orgId,
                'channel_id' => $this->channelIds['instagram'] ?? null,
                'framework' => 'multivariate',
                'hypothesis' => 'Testing different image styles will improve engagement',
                'status' => 'running',
                'created_at' => now()->subDays(rand(5, 20)),
                'campaign_id' => $this->campaignIds[0] ?? null,
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createExportBundles()
    {
        foreach (array_slice($this->orgIds, 0, 2) as $orgName => $orgId) {
            DB::table('cmis.export_bundles')->insert([
                'bundle_id' => Str::uuid(),
                'org_id' => $orgId,
                'name' => 'Q1 2025 Campaign Assets',
                'created_at' => now()->subDays(rand(1, 30)),
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createDatasetPackages()
    {
        $packages = [
            ['code' => 'global_brands', 'version' => '1.0', 'notes' => 'Global brand dataset'],
            ['code' => 'industry_benchmarks', 'version' => '2.1', 'notes' => 'Industry performance benchmarks'],
        ];

        foreach ($packages as $package) {
            DB::table('cmis.dataset_packages')->insert([
                'pkg_id' => Str::uuid(),
                'code' => $package['code'],
                'version' => $package['version'],
                'notes' => $package['notes'],
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createDataFeeds()
    {
        foreach (array_slice($this->orgIds, 0, 2) as $orgName => $orgId) {
            DB::table('cmis.data_feeds')->insert([
                'feed_id' => Str::uuid(),
                'org_id' => $orgId,
                'kind' => 'product_catalog',
                'source_meta' => json_encode(['url' => 'https://example.com/feed.xml', 'format' => 'xml']),
                'last_ingested' => now()->subHours(rand(1, 24)),
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createAnalyticsIntegrations()
    {
        if (empty($this->campaignIds)) return;

        foreach (array_slice($this->campaignIds, 0, 2) as $campaignId) {
            $campaign = DB::table('cmis.campaigns')->where('campaign_id', $campaignId)->first();

            DB::table('cmis.analytics_integrations')->insert([
                'integration_id' => Str::uuid(),
                'org_id' => $campaign->org_id,
                'campaign_id' => $campaignId,
                'platform' => 'google_analytics',
                'source_endpoint' => 'https://analytics.google.com/api',
                'mapping' => json_encode(['sessions' => 'visits', 'users' => 'unique_visitors']),
                'refresh_frequency' => 'daily',
                'last_synced_at' => now()->subHours(rand(1, 12)),
                'created_at' => now()->subDays(rand(10, 30)),
                'updated_at' => now()->subHours(rand(1, 12)),
                'deleted_at' => null,
                'provider' => 'google',
            ]);
        }
    }

    private function createOpsAudit()
    {
        foreach (array_slice($this->orgIds, 0, 2) as $orgName => $orgId) {
            $operations = ['data_sync', 'backup', 'cleanup', 'optimization'];

            foreach ($operations as $op) {
                DB::table('cmis.ops_audit')->insert([
                    'audit_id' => Str::uuid(),
                    'org_id' => $orgId,
                    'operation_name' => $op,
                    'status' => 'completed',
                    'executed_at' => now()->subDays(rand(1, 7)),
                    'details' => json_encode(['duration_ms' => rand(100, 5000), 'items_processed' => rand(10, 1000)]),
                    'deleted_at' => null,
                    'provider' => null,
                ]);
            }
        }
    }

    private function createSyncLogs()
    {
        $integrations = DB::table('cmis.integrations')->get();

        foreach ($integrations as $integration) {
            DB::table('cmis.sync_logs')->insert([
                'id' => Str::uuid(),
                'org_id' => $integration->org_id,
                'integration_id' => $integration->integration_id,
                'platform' => $integration->platform,
                'synced_at' => now()->subHours(rand(1, 24)),
                'status' => 'success',
                'items' => rand(10, 100),
                'level_counts' => json_encode(['posts' => rand(5, 50), 'comments' => rand(10, 100)]),
                'deleted_at' => null,
                'provider' => $integration->provider,
            ]);
        }
    }

    private function createScheduledReports()
    {
        if (empty($this->campaignIds)) return;

        foreach (array_slice($this->campaignIds, 0, 2) as $campaignId) {
            $campaign = DB::table('cmis.campaigns')->where('campaign_id', $campaignId)->first();

            DB::table('cmis.scheduled_reports')->insert([
                'schedule_id' => Str::uuid(),
                'report_type' => 'campaign_performance',
                'entity_id' => $campaignId,
                'frequency' => 'weekly',
                'format' => 'pdf',
                'delivery_method' => 'email',
                'recipients' => json_encode([array_values($this->userIds)[0]]),
                'config' => json_encode(['include_charts' => true, 'metrics' => ['impressions', 'clicks', 'conversions']]),
                'is_active' => true,
                'last_run_at' => now()->subWeek(),
                'next_run_at' => now()->addWeek(),
                'created_at' => now()->subMonths(1),
                'updated_at' => now()->subWeek(),
            ]);
        }
    }

    private function createAdAudiences()
    {
        $integrations = DB::table('cmis.integrations')->where('platform', 'facebook_ads')->get();

        foreach ($integrations as $integration) {
            DB::table('cmis.ad_audiences')->insert([
                'id' => Str::uuid(),
                'org_id' => $integration->org_id,
                'integration_id' => $integration->integration_id,
                'entity_level' => 'adset',
                'entity_external_id' => '120212345678902',
                'audience_type' => 'custom',
                'platform' => 'facebook',
                'demographics' => json_encode(['age_min' => 25, 'age_max' => 54, 'genders' => ['all']]),
                'interests' => json_encode(['Business', 'Technology', 'Enterprise Software']),
                'behaviors' => json_encode(['Business Decision Makers']),
                'location' => json_encode(['countries' => ['US', 'UK', 'CA']]),
                'keywords' => json_encode(['cloud software', 'enterprise solutions']),
                'custom_audience' => null,
                'lookalike_audience' => null,
                'advantage_plus_settings' => null,
                'created_at' => now()->subDays(rand(10, 30)),
                'updated_at' => now()->subDays(rand(1, 10)),
                'deleted_at' => null,
                'provider' => 'meta',
            ]);
        }
    }

    private function createAdMetrics()
    {
        $adEntities = DB::table('cmis.ad_entities')->get();

        foreach ($adEntities as $entity) {
            for ($i = 0; $i < 7; $i++) {
                DB::table('cmis.ad_metrics')->insert([
                    'id' => DB::raw('DEFAULT'),
                    'org_id' => $entity->org_id,
                    'integration_id' => $entity->integration_id,
                    'entity_level' => 'ad',
                    'entity_external_id' => $entity->ad_external_id,
                    'date_start' => now()->subDays(7 - $i)->toDateString(),
                    'date_stop' => now()->subDays(7 - $i)->toDateString(),
                    'spend' => rand(100, 500) + (rand(0, 99) / 100),
                    'impressions' => rand(5000, 20000),
                    'clicks' => rand(100, 500),
                    'actions' => json_encode(['link_click' => rand(50, 200), 'post_engagement' => rand(100, 400)]),
                    'conversions' => json_encode(['purchase' => rand(5, 25), 'lead' => rand(10, 50)]),
                    'created_at' => now(),
                    'deleted_at' => null,
                    'provider' => 'meta',
                ]);
            }
        }
    }

    private function createAdVariants()
    {
        $adCampaigns = DB::table('cmis.ad_campaigns')->get();

        foreach ($adCampaigns as $campaign) {
            $variants = ['A', 'B', 'C'];

            foreach ($variants as $variant) {
                DB::table('cmis.ad_variants')->insert([
                    'variant_id' => Str::uuid(),
                    'campaign_id' => $campaign->id,
                    'variant_type' => 'creative',
                    'variant_name' => "Variant $variant",
                    'variant_data' => json_encode(['headline' => "Headline $variant", 'image' => "image_$variant.jpg"]),
                    'budget_allocation' => 33.33,
                    'actual_spend' => rand(500, 1500),
                    'impressions' => rand(10000, 50000),
                    'clicks' => rand(200, 1000),
                    'conversions' => rand(10, 80),
                    'ctr' => rand(2, 5) + (rand(0, 99) / 100),
                    'conversion_rate' => rand(3, 12) + (rand(0, 99) / 100),
                    'is_active' => true,
                    'is_winner' => $variant === 'B',
                    'declared_winner_at' => $variant === 'B' ? now()->subDays(2) : null,
                    'created_at' => now()->subDays(15),
                    'updated_at' => now()->subDays(1),
                ]);
            }
        }
    }

    private function createSocialMetrics()
    {
        $socialAccounts = DB::table('cmis.social_accounts')->get();
        $socialPosts = DB::table('cmis.social_posts')->get();

        // Account metrics
        foreach ($socialAccounts as $account) {
            for ($i = 0; $i < 7; $i++) {
                DB::table('cmis.social_account_metrics')->insert([
                    'integration_id' => $account->integration_id,
                    'period_start' => now()->subDays(7 - $i)->toDateString(),
                    'period_end' => now()->subDays(7 - $i)->toDateString(),
                    'followers' => $account->followers_count + rand(-50, 100),
                    'reach' => rand(5000, 15000),
                    'impressions' => rand(8000, 20000),
                    'profile_views' => rand(200, 800),
                    'deleted_at' => null,
                    'provider' => 'meta',
                ]);
            }
        }

        // Post metrics
        foreach ($socialPosts as $post) {
            $metrics = ['likes', 'comments', 'shares', 'saves'];

            foreach ($metrics as $metric) {
                DB::table('cmis.social_post_metrics')->insert([
                    'id' => Str::uuid(),
                    'org_id' => $post->org_id,
                    'integration_id' => $post->integration_id,
                    'post_external_id' => $post->post_external_id,
                    'social_post_id' => $post->id,
                    'metric' => $metric,
                    'value' => rand(50, 500),
                    'fetched_at' => now(),
                    'created_at' => now(),
                    'deleted_at' => null,
                    'provider' => 'meta',
                ]);
            }
        }
    }

    private function createSQLSnippets()
    {
        $snippets = [
            ['name' => 'Get Campaign Performance', 'sql' => 'SELECT campaign_id, SUM(spend) as total_spend FROM cmis.performance_metrics GROUP BY campaign_id', 'description' => 'Aggregate campaign spend'],
            ['name' => 'Top Performing Posts', 'sql' => 'SELECT * FROM cmis.social_posts ORDER BY (metrics->\'likes\')::int DESC LIMIT 10', 'description' => 'Get top 10 posts by likes'],
        ];

        foreach ($snippets as $snippet) {
            DB::table('cmis.sql_snippets')->insert([
                'snippet_id' => Str::uuid(),
                'name' => $snippet['name'],
                'sql' => $snippet['sql'],
                'description' => $snippet['description'],
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }

    private function createFlows()
    {
        foreach (array_slice($this->orgIds, 0, 2) as $orgName => $orgId) {
            $flowId = Str::uuid();

            DB::table('cmis.flows')->insert([
                'flow_id' => $flowId,
                'org_id' => $orgId,
                'name' => 'Auto-Publish Workflow',
                'description' => 'Automated content publishing workflow',
                'version' => '1.0.0',
                'tags' => json_encode(['automation', 'publishing']),
                'enabled' => true,
                'deleted_at' => null,
                'provider' => null,
            ]);

            // Flow steps
            $steps = [
                ['ord' => 1, 'type' => 'trigger', 'name' => 'New Post Created', 'input_map' => json_encode(['event' => 'post.created'])],
                ['ord' => 2, 'type' => 'condition', 'name' => 'Check Approval Status', 'input_map' => json_encode(['field' => 'status'])],
                ['ord' => 3, 'type' => 'action', 'name' => 'Publish to Platform', 'input_map' => json_encode(['platform' => 'instagram'])],
            ];

            foreach ($steps as $step) {
                DB::table('cmis.flow_steps')->insert([
                    'step_id' => Str::uuid(),
                    'flow_id' => $flowId,
                    'ord' => $step['ord'],
                    'type' => $step['type'],
                    'name' => $step['name'],
                    'input_map' => $step['input_map'],
                    'config' => json_encode([]),
                    'output_map' => json_encode([]),
                    'condition' => $step['type'] === 'condition' ? json_encode(['operator' => 'equals', 'value' => 'approved']) : null,
                    'deleted_at' => null,
                    'provider' => null,
                ]);
            }
        }
    }

    private function createMetaDocumentation()
    {
        $docs = [
            ['meta_key' => 'campaign.objective.awareness', 'meta_value' => 'Campaigns focused on building brand recognition and visibility'],
            ['meta_key' => 'campaign.objective.conversion', 'meta_value' => 'Campaigns optimized for driving specific actions like purchases or sign-ups'],
            ['meta_key' => 'field.headline.best_practices', 'meta_value' => 'Keep headlines under 100 characters, use action words, create urgency'],
        ];

        foreach ($docs as $doc) {
            DB::table('cmis.meta_documentation')->insert([
                'doc_id' => Str::uuid(),
                'meta_key' => $doc['meta_key'],
                'meta_value' => $doc['meta_value'],
                'updated_by' => array_values($this->userIds)[0] ?? null,
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ]);
        }
    }
}
