<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET search_path TO cmis,public');
        
        // activity_logs table (24 errors)
        if (!$this->tableExists('cmis.activity_logs')) {
            DB::statement("CREATE TABLE cmis.activity_logs (
                log_id UUID PRIMARY KEY,
                org_id UUID,
                user_id UUID,
                activity_type VARCHAR(100),
                description TEXT,
                metadata JSONB,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.activity_logs\n";
        }
        
        // settings table - MOVED to 2025_11_25_230000_create_settings_table_standalone.php
        // The settings table with deleted_at column is now created in a later migration
        // to ensure proper Schema::hasTable checks work correctly
        
        // schedules table (22 errors)
        if (!$this->tableExists('cmis.schedules')) {
            DB::statement("CREATE TABLE cmis.schedules (
                schedule_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                entity_type VARCHAR(100),
                entity_id UUID,
                scheduled_at TIMESTAMP WITH TIME ZONE,
                status VARCHAR(50) DEFAULT 'pending',
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.schedules\n";
        }
        
        // campaign_analytics table (22 errors)
        if (!$this->tableExists('cmis.campaign_analytics')) {
            DB::statement("CREATE TABLE cmis.campaign_analytics (
                analytics_id UUID PRIMARY KEY,
                campaign_id UUID NOT NULL,
                org_id UUID NOT NULL,
                metrics JSONB,
                date DATE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.campaign_analytics\n";
        }
        
        // social_posts_v2 table (20 errors)
        if (!$this->tableExists('cmis.social_posts_v2')) {
            DB::statement("CREATE TABLE cmis.social_posts_v2 (
                social_post_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                content TEXT,
                platform VARCHAR(50),
                status VARCHAR(50) DEFAULT 'draft',
                published_at TIMESTAMP WITH TIME ZONE,
                post_id UUID,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.social_posts_v2\n";
        }
        
        // social_accounts_v2 table (20 errors)
        if (!$this->tableExists('cmis.social_accounts_v2')) {
            DB::statement("CREATE TABLE cmis.social_accounts_v2 (
                account_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                platform VARCHAR(50),
                username VARCHAR(255),
                is_active BOOLEAN DEFAULT true,
                credentials JSONB,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.social_accounts_v2\n";
        }
        
        // content_plans_v2 table (20 errors)
        if (!$this->tableExists('cmis.content_plans_v2')) {
            DB::statement("CREATE TABLE cmis.content_plans_v2 (
                plan_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                name VARCHAR(255),
                description TEXT,
                start_date DATE,
                end_date DATE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.content_plans_v2\n";
        }
        
        // scheduled_social_posts_v2 table (6 errors)
        if (!$this->tableExists('cmis.scheduled_social_posts_v2')) {
            DB::statement("CREATE TABLE cmis.scheduled_social_posts_v2 (
                scheduled_post_id UUID PRIMARY KEY,
                social_post_id UUID NOT NULL,
                org_id UUID NOT NULL,
                scheduled_at TIMESTAMP WITH TIME ZONE,
                status VARCHAR(50) DEFAULT 'pending',
                post_id UUID,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.scheduled_social_posts_v2\n";
        }
        
        // embeddings_cache table (4 errors)
        if (!$this->tableExists('cmis.embeddings_cache')) {
            DB::statement("CREATE TABLE cmis.embeddings_cache (
                cache_id UUID PRIMARY KEY,
                content_hash VARCHAR(64) NOT NULL,
                embedding vector(768),
                model VARCHAR(100),
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                UNIQUE(content_hash, model)
            )");
            echo "✓ Created table: cmis.embeddings_cache\n";
        }
        
        // campaign_metrics table (4 errors)
        if (!$this->tableExists('cmis.campaign_metrics')) {
            DB::statement("CREATE TABLE cmis.campaign_metrics (
                metric_id UUID PRIMARY KEY,
                campaign_id UUID NOT NULL,
                org_id UUID NOT NULL,
                metric_name VARCHAR(100),
                value DECIMAL(20,4),
                recorded_at TIMESTAMP WITH TIME ZONE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.campaign_metrics\n";
        }
        
        echo "\n✅ Migration complete! Created 10 additional tables.\n";
    }
    
    private function tableExists(string $tableName): bool
    {
        $parts = explode('.', $tableName);
        $schema = $parts[0];
        $table = $parts[1];
        
        $exists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.tables 
                WHERE table_schema = ? AND table_name = ?
            ) as exists
        ", [$schema, $table]);
        
        return $exists->exists;
    }

    public function down(): void
    {
        DB::statement('SET search_path TO cmis,public');
        
        $tables = [
            'campaign_metrics', 'embeddings_cache', 'scheduled_social_posts_v2',
            'content_plans_v2', 'social_accounts_v2', 'social_posts_v2',
            'campaign_analytics', 'schedules', 'activity_logs'
            // Note: 'settings' is handled by 2025_11_25_230000_create_settings_table_standalone
        ];
        
        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS cmis.{$table} CASCADE");
        }
    }
};
