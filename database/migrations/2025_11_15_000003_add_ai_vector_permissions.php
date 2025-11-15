<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * نظام صلاحيات شامل لـ AI و Vector Embeddings
     * يغطي جميع الأنظمة: توليد المحتوى، الفيديو، الصور، الجدولة، الإعلانات
     */
    public function up(): void
    {
        $permissions = [
            // ========================================
            // AI Content Generation
            // ========================================
            ['code' => 'ai.generate_content', 'name' => 'Generate AI Content', 'category' => 'ai_generation', 'is_dangerous' => false],
            ['code' => 'ai.generate_campaign', 'name' => 'Generate AI Campaign', 'category' => 'ai_generation', 'is_dangerous' => false],
            ['code' => 'ai.generate_social_post', 'name' => 'Generate Social Media Post', 'category' => 'ai_generation', 'is_dangerous' => false],
            ['code' => 'ai.generate_ad_copy', 'name' => 'Generate Ad Copy', 'category' => 'ai_generation', 'is_dangerous' => false],
            ['code' => 'ai.generate_email', 'name' => 'Generate Email Content', 'category' => 'ai_generation', 'is_dangerous' => false],

            // ========================================
            // AI Video & Script Generation
            // ========================================
            ['code' => 'ai.generate_video_script', 'name' => 'Generate Video Scripts', 'category' => 'ai_video', 'is_dangerous' => false],
            ['code' => 'ai.generate_video_prompt', 'name' => 'Generate Video Generation Prompts', 'category' => 'ai_video', 'is_dangerous' => false],
            ['code' => 'ai.generate_storyboard', 'name' => 'Generate Video Storyboards', 'category' => 'ai_video', 'is_dangerous' => false],
            ['code' => 'ai.generate_voiceover', 'name' => 'Generate Voiceover Scripts', 'category' => 'ai_video', 'is_dangerous' => false],

            // ========================================
            // AI Image & Design Generation
            // ========================================
            ['code' => 'ai.generate_image_prompt', 'name' => 'Generate Image Prompts', 'category' => 'ai_image', 'is_dangerous' => false],
            ['code' => 'ai.generate_design_description', 'name' => 'Generate Design Descriptions', 'category' => 'ai_image', 'is_dangerous' => false],
            ['code' => 'ai.generate_creative_brief', 'name' => 'Generate Creative Briefs', 'category' => 'ai_image', 'is_dangerous' => false],
            ['code' => 'ai.generate_visual_concepts', 'name' => 'Generate Visual Concepts', 'category' => 'ai_image', 'is_dangerous' => false],

            // ========================================
            // Content Planning & Strategy
            // ========================================
            ['code' => 'content.plan', 'name' => 'Create Content Plans', 'category' => 'content_planning', 'is_dangerous' => false],
            ['code' => 'content.plan_edit', 'name' => 'Edit Content Plans', 'category' => 'content_planning', 'is_dangerous' => false],
            ['code' => 'content.plan_approve', 'name' => 'Approve Content Plans', 'category' => 'content_planning', 'is_dangerous' => false],
            ['code' => 'content.plan_delete', 'name' => 'Delete Content Plans', 'category' => 'content_planning', 'is_dangerous' => true],

            // ========================================
            // Scheduling & Publishing
            // ========================================
            ['code' => 'schedule.create', 'name' => 'Schedule Posts', 'category' => 'scheduling', 'is_dangerous' => false],
            ['code' => 'schedule.edit', 'name' => 'Edit Scheduled Posts', 'category' => 'scheduling', 'is_dangerous' => false],
            ['code' => 'schedule.delete', 'name' => 'Delete Scheduled Posts', 'category' => 'scheduling', 'is_dangerous' => false],
            ['code' => 'schedule.approve', 'name' => 'Approve Scheduled Posts', 'category' => 'scheduling', 'is_dangerous' => false],
            ['code' => 'schedule.publish_now', 'name' => 'Publish Immediately', 'category' => 'scheduling', 'is_dangerous' => false],

            // ========================================
            // Paid Ads Management
            // ========================================
            ['code' => 'ads.manage_campaigns', 'name' => 'Manage Ad Campaigns', 'category' => 'paid_ads', 'is_dangerous' => false],
            ['code' => 'ads.manage_budgets', 'name' => 'Manage Ad Budgets', 'category' => 'paid_ads', 'is_dangerous' => true],
            ['code' => 'ads.manage_targeting', 'name' => 'Manage Ad Targeting', 'category' => 'paid_ads', 'is_dangerous' => false],
            ['code' => 'ads.view_performance', 'name' => 'View Ad Performance', 'category' => 'paid_ads', 'is_dangerous' => false],
            ['code' => 'ads.optimize', 'name' => 'Optimize Ad Campaigns', 'category' => 'paid_ads', 'is_dangerous' => false],

            // ========================================
            // Vector Embeddings & Semantic Search
            // ========================================
            ['code' => 'vector.semantic_search', 'name' => 'Use Semantic Search', 'category' => 'vector_embeddings', 'is_dangerous' => false],
            ['code' => 'vector.hybrid_search', 'name' => 'Use Hybrid Search', 'category' => 'vector_embeddings', 'is_dangerous' => false],
            ['code' => 'vector.smart_context', 'name' => 'Use Smart Context Loader', 'category' => 'vector_embeddings', 'is_dangerous' => false],
            ['code' => 'vector.register_knowledge', 'name' => 'Register New Knowledge', 'category' => 'vector_embeddings', 'is_dangerous' => false],
            ['code' => 'vector.manage_embeddings', 'name' => 'Manage Embeddings', 'category' => 'vector_embeddings', 'is_dangerous' => false],
            ['code' => 'vector.process_queue', 'name' => 'Process Embedding Queue', 'category' => 'vector_embeddings', 'is_dangerous' => false],
            ['code' => 'vector.view_analytics', 'name' => 'View Embedding Analytics', 'category' => 'vector_embeddings', 'is_dangerous' => false],
            ['code' => 'vector.manage_intents', 'name' => 'Manage Intent Mappings', 'category' => 'vector_embeddings', 'is_dangerous' => false],

            // ========================================
            // AI Prompts Management
            // ========================================
            ['code' => 'prompts.view', 'name' => 'View Prompt Templates', 'category' => 'ai_prompts', 'is_dangerous' => false],
            ['code' => 'prompts.create', 'name' => 'Create Prompt Templates', 'category' => 'ai_prompts', 'is_dangerous' => false],
            ['code' => 'prompts.edit', 'name' => 'Edit Prompt Templates', 'category' => 'ai_prompts', 'is_dangerous' => false],
            ['code' => 'prompts.delete', 'name' => 'Delete Prompt Templates', 'category' => 'ai_prompts', 'is_dangerous' => true],
            ['code' => 'prompts.share', 'name' => 'Share Prompt Templates', 'category' => 'ai_prompts', 'is_dangerous' => false],

            // ========================================
            // AI Recommendations & Insights
            // ========================================
            ['code' => 'ai.view_recommendations', 'name' => 'View AI Recommendations', 'category' => 'ai_insights', 'is_dangerous' => false],
            ['code' => 'ai.view_insights', 'name' => 'View AI Insights', 'category' => 'ai_insights', 'is_dangerous' => false],
            ['code' => 'ai.view_predictions', 'name' => 'View AI Predictions', 'category' => 'ai_insights', 'is_dangerous' => false],
            ['code' => 'ai.view_trends', 'name' => 'View AI Trend Analysis', 'category' => 'ai_insights', 'is_dangerous' => false],

            // ========================================
            // Knowledge Management
            // ========================================
            ['code' => 'knowledge.view', 'name' => 'View Knowledge Base', 'category' => 'knowledge', 'is_dangerous' => false],
            ['code' => 'knowledge.create', 'name' => 'Create Knowledge Items', 'category' => 'knowledge', 'is_dangerous' => false],
            ['code' => 'knowledge.edit', 'name' => 'Edit Knowledge Items', 'category' => 'knowledge', 'is_dangerous' => false],
            ['code' => 'knowledge.delete', 'name' => 'Delete Knowledge Items', 'category' => 'knowledge', 'is_dangerous' => true],
            ['code' => 'knowledge.verify', 'name' => 'Verify Knowledge Items', 'category' => 'knowledge', 'is_dangerous' => false],

            // ========================================
            // Workflow & Automation
            // ========================================
            ['code' => 'workflow.create', 'name' => 'Create Workflows', 'category' => 'automation', 'is_dangerous' => false],
            ['code' => 'workflow.edit', 'name' => 'Edit Workflows', 'category' => 'automation', 'is_dangerous' => false],
            ['code' => 'workflow.delete', 'name' => 'Delete Workflows', 'category' => 'automation', 'is_dangerous' => true],
            ['code' => 'workflow.execute', 'name' => 'Execute Workflows', 'category' => 'automation', 'is_dangerous' => false],

            // ========================================
            // Integration Management (موسع)
            // ========================================
            ['code' => 'integration.meta_ads', 'name' => 'Manage Meta Ads Integration', 'category' => 'integrations', 'is_dangerous' => false],
            ['code' => 'integration.google_ads', 'name' => 'Manage Google Ads Integration', 'category' => 'integrations', 'is_dangerous' => false],
            ['code' => 'integration.linkedin_ads', 'name' => 'Manage LinkedIn Ads Integration', 'category' => 'integrations', 'is_dangerous' => false],
            ['code' => 'integration.tiktok_ads', 'name' => 'Manage TikTok Ads Integration', 'category' => 'integrations', 'is_dangerous' => false],
        ];

        foreach ($permissions as $permission) {
            DB::table('cmis.permissions')->insert([
                'permission_id' => Str::uuid(),
                'permission_code' => $permission['code'],
                'permission_name' => $permission['name'],
                'category' => $permission['category'],
                'description' => null,
                'is_dangerous' => $permission['is_dangerous'],
                'deleted_at' => null,
                'provider' => 'cmis_ai_vector_v2',
            ]);
        }

        // تسجيل في Audit Log
        DB::insert(
            'INSERT INTO cmis_audit.logs (event_type, event_source, description, created_at) VALUES (?, ?, ?, ?)',
            [
                'permissions_added',
                'ai_vector_v2_migration',
                '✨ Added ' . count($permissions) . ' new AI & Vector Embeddings permissions',
                now()
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('cmis.permissions')
            ->where('provider', 'cmis_ai_vector_v2')
            ->delete();
    }
};
