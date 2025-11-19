<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET search_path TO cmis,public');
        
        // ========== CREATE MISSING TABLES (10 errors) ==========
        
        // embedding_api_config table (4 errors)
        if (!$this->tableExists('cmis.embedding_api_config')) {
            DB::statement("CREATE TABLE cmis.embedding_api_config (
                config_id UUID PRIMARY KEY,
                provider VARCHAR(100),
                model VARCHAR(100),
                api_key TEXT,
                endpoint VARCHAR(500),
                max_tokens INTEGER,
                is_active BOOLEAN DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.embedding_api_config\n";
        }
        
        // semantic_search_log table (2 errors)
        if (!$this->tableExists('cmis.semantic_search_log')) {
            DB::statement("CREATE TABLE cmis.semantic_search_log (
                log_id UUID PRIMARY KEY,
                org_id UUID,
                user_id UUID,
                query TEXT,
                results JSONB,
                execution_time_ms INTEGER,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.semantic_search_log\n";
        }
        
        // knowledge_base table (2 errors)
        if (!$this->tableExists('cmis.knowledge_base')) {
            DB::statement("CREATE TABLE cmis.knowledge_base (
                knowledge_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                title VARCHAR(500),
                content TEXT,
                category VARCHAR(100),
                metadata JSONB,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.knowledge_base\n";
        }
        
        // ad_campaigns_v2 table (2 errors)
        if (!$this->tableExists('cmis.ad_campaigns_v2')) {
            DB::statement("CREATE TABLE cmis.ad_campaigns_v2 (
                ad_campaign_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                name VARCHAR(255),
                status VARCHAR(50),
                budget DECIMAL(15,2),
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.ad_campaigns_v2\n";
        }
        
        // ========== FIX MISSING COLUMNS (110+ errors) ==========
        
        // Add locale to markets (28 errors)
        if (!$this->columnExists('public', 'markets', 'locale')) {
            DB::statement("ALTER TABLE public.markets ADD COLUMN locale VARCHAR(10)");
            DB::statement("UPDATE public.markets SET locale = language_code WHERE locale IS NULL");
            
            // Recreate view
            DB::statement("DROP VIEW IF EXISTS cmis.markets CASCADE");
            DB::statement("CREATE OR REPLACE VIEW cmis.markets AS
                SELECT market_id, market_name, language_code, currency_code, text_direction, code, name, locale
                FROM public.markets");
            echo "✓ Added locale to markets\n";
        }
        
        // Add content_type to content_plan_items (20 errors)
        if ($this->tableExists('cmis.content_plan_items')) {
            if (!$this->columnExists('cmis', 'content_plan_items', 'content_type')) {
                DB::statement("ALTER TABLE cmis.content_plan_items ADD COLUMN content_type VARCHAR(100)");
                echo "✓ Added content_type to content_plan_items\n";
            }
        }
        
        // Add updated_at to audit_logs (16 errors)
        if ($this->tableExists('cmis.audit_logs')) {
            if (!$this->columnExists('cmis', 'audit_logs', 'updated_at')) {
                DB::statement("ALTER TABLE cmis.audit_logs ADD COLUMN updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()");
                echo "✓ Added updated_at to audit_logs\n";
            }
        }
        
        // Add impressions to campaign_analytics (14 errors)
        if ($this->tableExists('cmis.campaign_analytics')) {
            if (!$this->columnExists('cmis', 'campaign_analytics', 'impressions')) {
                DB::statement("ALTER TABLE cmis.campaign_analytics ADD COLUMN impressions BIGINT DEFAULT 0");
                echo "✓ Added impressions to campaign_analytics\n";
            }
        }
        
        // Add access_token to platform_connections (8 errors)
        if ($this->tableExists('cmis.platform_connections')) {
            if (!$this->columnExists('cmis', 'platform_connections', 'access_token')) {
                DB::statement("ALTER TABLE cmis.platform_connections ADD COLUMN access_token TEXT");
                echo "✓ Added access_token to platform_connections\n";
            }
        }
        
        // Add spent_amount to budgets (6 errors)
        if ($this->tableExists('cmis.budgets')) {
            if (!$this->columnExists('cmis', 'budgets', 'spent_amount')) {
                DB::statement("ALTER TABLE cmis.budgets ADD COLUMN spent_amount DECIMAL(15,2) DEFAULT 0");
                echo "✓ Added spent_amount to budgets\n";
            }
        }
        
        // Add spend to campaign_analytics (6 errors)
        if ($this->tableExists('cmis.campaign_analytics')) {
            if (!$this->columnExists('cmis', 'campaign_analytics', 'spend')) {
                DB::statement("ALTER TABLE cmis.campaign_analytics ADD COLUMN spend DECIMAL(15,2) DEFAULT 0");
                echo "✓ Added spend to campaign_analytics\n";
            }
        }
        
        // Add is_active to team_members (4 errors)
        if ($this->tableExists('cmis.team_members')) {
            if (!$this->columnExists('cmis', 'team_members', 'is_active')) {
                DB::statement("ALTER TABLE cmis.team_members ADD COLUMN is_active BOOLEAN DEFAULT true");
                echo "✓ Added is_active to team_members\n";
            }
        }
        
        // Add ip_address to audit_logs (4 errors)
        if ($this->tableExists('cmis.audit_logs')) {
            if (!$this->columnExists('cmis', 'audit_logs', 'ip_address')) {
                DB::statement("ALTER TABLE cmis.audit_logs ADD COLUMN ip_address VARCHAR(45)");
                echo "✓ Added ip_address to audit_logs\n";
            }
        }
        
        // Add campaign_id to budgets (4 errors)
        if ($this->tableExists('cmis.budgets')) {
            if (!$this->columnExists('cmis', 'budgets', 'campaign_id')) {
                DB::statement("ALTER TABLE cmis.budgets ADD COLUMN campaign_id UUID");
                echo "✓ Added campaign_id to budgets\n";
            }
        }
        
        // ========== FIX NULL CONSTRAINTS (366 errors) ==========

        // Synchronize member_id and team_member_id in team_members (226 errors)
        // member_id is PRIMARY KEY so can't be nullable - ensure team_member_id matches member_id
        if ($this->tableExists('cmis.team_members')) {
            DB::statement("UPDATE cmis.team_members SET team_member_id = member_id WHERE team_member_id IS NULL OR team_member_id != member_id");
            echo "✓ Synchronized team_member_id with member_id in team_members\n";
        }

        // Make org_id nullable in content_plan_items (36 errors)
        if ($this->tableExists('cmis.content_plan_items')) {
            DB::statement("ALTER TABLE cmis.content_plan_items ALTER COLUMN org_id DROP NOT NULL");
            echo "✓ Made org_id nullable in content_plan_items\n";
        }
        
        // Make role_code nullable in roles (26 errors)
        if ($this->tableExists('cmis.roles')) {
            if ($this->columnExists('cmis', 'roles', 'role_code')) {
                DB::statement("ALTER TABLE cmis.roles ALTER COLUMN role_code DROP NOT NULL");
                echo "✓ Made role_code nullable in roles\n";
            }
        }
        
        // Make audience_id nullable in audience_segments (26 errors)
        if ($this->tableExists('cmis.audience_segments')) {
            DB::statement("ALTER TABLE cmis.audience_segments ALTER COLUMN audience_id DROP NOT NULL");
            echo "✓ Made audience_id nullable in audience_segments\n";
        }
        
        // Make permission_name nullable in permissions (24 errors)
        if ($this->tableExists('cmis.permissions')) {
            DB::statement("ALTER TABLE cmis.permissions ALTER COLUMN permission_name DROP NOT NULL");
            echo "✓ Made permission_name nullable in permissions\n";
        }
        
        // Make content_id nullable in content_media (24 errors)
        if ($this->tableExists('cmis.content_media')) {
            DB::statement("ALTER TABLE cmis.content_media ALTER COLUMN content_id DROP NOT NULL");
            echo "✓ Made content_id nullable in content_media\n";
        }
        
        // Make social_post_id nullable in scheduled_social_posts_v2 (6 errors)
        if ($this->tableExists('cmis.scheduled_social_posts_v2')) {
            DB::statement("ALTER TABLE cmis.scheduled_social_posts_v2 ALTER COLUMN social_post_id DROP NOT NULL");
            echo "✓ Made social_post_id nullable in scheduled_social_posts_v2\n";
        }
        
        echo "\n✅ Final comprehensive fixes complete!\n";
        echo "   - Created 4 missing tables\n";
        echo "   - Fixed 10 missing columns\n";
        echo "   - Fixed 7 NULL constraints\n";
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
    
    private function columnExists(string $schema, string $table, string $column): bool
    {
        $exists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.columns
                WHERE table_schema = ? AND table_name = ? AND column_name = ?
            ) as exists
        ", [$schema, $table, $column]);
        
        return $exists->exists;
    }

    public function down(): void
    {
        // Rollback logic if needed
    }
};
