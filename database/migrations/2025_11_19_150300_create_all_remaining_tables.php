<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET search_path TO cmis,public');
        
        // audience_segments table (26 errors)
        if (!$this->tableExists('cmis.audience_segments')) {
            DB::statement("CREATE TABLE cmis.audience_segments (
                segment_id UUID PRIMARY KEY,
                audience_id UUID NOT NULL,
                org_id UUID NOT NULL,
                name VARCHAR(255),
                criteria JSONB,
                size INTEGER,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.audience_segments\n";
        }
        
        // analytics_snapshots table (26 errors)
        if (!$this->tableExists('cmis.analytics_snapshots')) {
            DB::statement("CREATE TABLE cmis.analytics_snapshots (
                snapshot_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                campaign_id UUID,
                metrics JSONB,
                snapshot_date TIMESTAMP WITH TIME ZONE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.analytics_snapshots\n";
        }
        
        // analytics_reports table (26 errors)
        if (!$this->tableExists('cmis.analytics_reports')) {
            DB::statement("CREATE TABLE cmis.analytics_reports (
                report_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                type VARCHAR(100),
                data JSONB,
                period_start TIMESTAMP WITH TIME ZONE,
                period_end TIMESTAMP WITH TIME ZONE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.analytics_reports\n";
        }
        
        // activities table (26 errors)
        if (!$this->tableExists('cmis.activities')) {
            DB::statement("CREATE TABLE cmis.activities (
                activity_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                user_id UUID,
                action VARCHAR(100),
                subject_type VARCHAR(100),
                subject_id UUID,
                metadata JSONB,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.activities\n";
        }
        
        // workflows table (24 errors)
        if (!$this->tableExists('cmis.workflows')) {
            DB::statement("CREATE TABLE cmis.workflows (
                workflow_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                name VARCHAR(255),
                trigger VARCHAR(100),
                actions JSONB,
                is_active BOOLEAN DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                deleted_at TIMESTAMP WITH TIME ZONE
            )");
            echo "✓ Created table: cmis.workflows\n";
        }
        
        // tags table (24 errors)
        if (!$this->tableExists('cmis.tags')) {
            DB::statement("CREATE TABLE cmis.tags (
                tag_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                name VARCHAR(100),
                slug VARCHAR(100),
                color VARCHAR(7),
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.tags\n";
        }
        
        // notification_preferences table (24 errors)
        if (!$this->tableExists('cmis.notification_preferences')) {
            DB::statement("CREATE TABLE cmis.notification_preferences (
                preference_id UUID PRIMARY KEY,
                user_id UUID NOT NULL,
                org_id UUID NOT NULL,
                channel VARCHAR(50),
                event_type VARCHAR(100),
                is_enabled BOOLEAN DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.notification_preferences\n";
        }
        
        // metrics table (24 errors)
        if (!$this->tableExists('cmis.metrics')) {
            DB::statement("CREATE TABLE cmis.metrics (
                metric_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                campaign_id UUID,
                metric_type VARCHAR(100),
                value DECIMAL(20,4),
                recorded_at TIMESTAMP WITH TIME ZONE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.metrics\n";
        }
        
        // content_media table (24 errors)
        if (!$this->tableExists('cmis.content_media')) {
            DB::statement("CREATE TABLE cmis.content_media (
                media_id UUID PRIMARY KEY,
                content_id UUID NOT NULL,
                org_id UUID NOT NULL,
                url VARCHAR(500),
                media_type VARCHAR(50),
                size BIGINT,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.content_media\n";
        }
        
        // campaign_budgets table (24 errors)
        if (!$this->tableExists('cmis.campaign_budgets')) {
            DB::statement("CREATE TABLE cmis.campaign_budgets (
                budget_id UUID PRIMARY KEY,
                campaign_id UUID NOT NULL,
                org_id UUID NOT NULL,
                amount DECIMAL(15,2),
                currency VARCHAR(3) DEFAULT 'USD',
                period VARCHAR(50),
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.campaign_budgets\n";
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
            'campaign_budgets', 'content_media', 'metrics', 'notification_preferences',
            'tags', 'workflows', 'activities', 'analytics_reports', 'analytics_snapshots',
            'audience_segments'
        ];
        
        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS cmis.{$table} CASCADE");
        }
    }
};
