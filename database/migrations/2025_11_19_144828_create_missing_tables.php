<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set search path
        DB::statement('SET search_path TO cmis,public');
        
        // team_members table (230 failures)
        if (!Schema::hasTable('cmis.team_members')) {
            DB::statement("CREATE TABLE cmis.team_members (
                member_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                user_id UUID NOT NULL,
                role VARCHAR(50),
                joined_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.team_members\n";
        }
        
        // leads table (120 failures)
        if (!Schema::hasTable('cmis.leads')) {
            DB::statement("CREATE TABLE cmis.leads (
                lead_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                name VARCHAR(255),
                email VARCHAR(255),
                phone VARCHAR(50),
                source VARCHAR(100),
                status VARCHAR(50) DEFAULT 'new',
                metadata JSONB,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.leads\n";
        }
        
        // webhooks table (78 failures)
        if (!Schema::hasTable('cmis.webhooks')) {
            DB::statement("CREATE TABLE cmis.webhooks (
                webhook_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                url VARCHAR(500) NOT NULL,
                event_type VARCHAR(100) NOT NULL,
                is_active BOOLEAN DEFAULT true,
                secret VARCHAR(255),
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.webhooks\n";
        }
        
        // contacts table (50 failures)
        if (!Schema::hasTable('cmis.contacts')) {
            DB::statement("CREATE TABLE cmis.contacts (
                contact_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                name VARCHAR(255),
                email VARCHAR(255),
                phone VARCHAR(50),
                company VARCHAR(255),
                metadata JSONB,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.contacts\n";
        }
        
        // knowledge_index table (48 failures)
        if (!Schema::hasTable('cmis.knowledge_index')) {
            DB::statement("CREATE TABLE cmis.knowledge_index (
                index_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                content TEXT,
                embedding vector(768),
                metadata JSONB,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.knowledge_index\n";
        }
        
        // subscriptions table (46 failures)
        if (!Schema::hasTable('cmis.subscriptions')) {
            DB::statement("CREATE TABLE cmis.subscriptions (
                subscription_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                plan VARCHAR(100),
                status VARCHAR(50) DEFAULT 'active',
                starts_at TIMESTAMP WITH TIME ZONE,
                ends_at TIMESTAMP WITH TIME ZONE,
                metadata JSONB,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.subscriptions\n";
        }
        
        // api_logs table (40 failures)
        if (!Schema::hasTable('cmis.api_logs')) {
            DB::statement("CREATE TABLE cmis.api_logs (
                log_id UUID PRIMARY KEY,
                org_id UUID,
                user_id UUID,
                endpoint VARCHAR(500),
                method VARCHAR(10),
                status_code INTEGER,
                request_body JSONB,
                response_body JSONB,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.api_logs\n";
        }
        
        // content_plan_items table (36 failures)
        if (!Schema::hasTable('cmis.content_plan_items')) {
            DB::statement("CREATE TABLE cmis.content_plan_items (
                item_id UUID PRIMARY KEY,
                plan_id UUID NOT NULL,
                org_id UUID NOT NULL,
                title VARCHAR(255),
                content TEXT,
                scheduled_for TIMESTAMP WITH TIME ZONE,
                status VARCHAR(50) DEFAULT 'draft',
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.content_plan_items\n";
        }
        
        // offerings table (34 failures)
        if (!Schema::hasTable('cmis.offerings')) {
            DB::statement("CREATE TABLE cmis.offerings (
                offering_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                name VARCHAR(255),
                description TEXT,
                price DECIMAL(10,2),
                currency VARCHAR(3) DEFAULT 'USD',
                is_active BOOLEAN DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.offerings\n";
        }
        
        // content table (32 failures)
        if (!Schema::hasTable('cmis.content')) {
            DB::statement("CREATE TABLE cmis.content (
                content_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                title VARCHAR(255),
                body TEXT,
                content_type VARCHAR(50),
                status VARCHAR(50) DEFAULT 'draft',
                metadata JSONB,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.content\n";
        }
        
        // Additional high-impact tables
        
        // platform_connections table (30 failures)
        if (!Schema::hasTable('cmis.platform_connections')) {
            DB::statement("CREATE TABLE cmis.platform_connections (
                connection_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                platform VARCHAR(50),
                credentials JSONB,
                is_active BOOLEAN DEFAULT true,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.platform_connections\n";
        }
        
        // assets table (30 failures)
        if (!Schema::hasTable('cmis.assets')) {
            DB::statement("CREATE TABLE cmis.assets (
                asset_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                name VARCHAR(255),
                url VARCHAR(500),
                type VARCHAR(50),
                size BIGINT,
                metadata JSONB,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.assets\n";
        }
        
        // invoices table (28 failures)
        if (!Schema::hasTable('cmis.invoices')) {
            DB::statement("CREATE TABLE cmis.invoices (
                invoice_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                amount DECIMAL(10,2),
                currency VARCHAR(3) DEFAULT 'USD',
                status VARCHAR(50) DEFAULT 'pending',
                due_date DATE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.invoices\n";
        }
        
        // audiences table (28 failures)
        if (!Schema::hasTable('cmis.audiences')) {
            DB::statement("CREATE TABLE cmis.audiences (
                audience_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                name VARCHAR(255),
                description TEXT,
                criteria JSONB,
                size INTEGER,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.audiences\n";
        }
        
        // templates table (26 failures)
        if (!Schema::hasTable('cmis.templates')) {
            DB::statement("CREATE TABLE cmis.templates (
                template_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                name VARCHAR(255),
                content TEXT,
                type VARCHAR(50),
                metadata JSONB,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.templates\n";
        }
        
        // posts table (26 failures)
        if (!Schema::hasTable('cmis.posts')) {
            DB::statement("CREATE TABLE cmis.posts (
                post_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                title VARCHAR(255),
                content TEXT,
                status VARCHAR(50) DEFAULT 'draft',
                published_at TIMESTAMP WITH TIME ZONE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.posts\n";
        }
        
        // custom_fields table (26 failures)
        if (!Schema::hasTable('cmis.custom_fields')) {
            DB::statement("CREATE TABLE cmis.custom_fields (
                field_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                name VARCHAR(255),
                field_type VARCHAR(50),
                options JSONB,
                entity_type VARCHAR(100),
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.custom_fields\n";
        }
        
        // comments table (26 failures)
        if (!Schema::hasTable('cmis.comments')) {
            DB::statement("CREATE TABLE cmis.comments (
                comment_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                user_id UUID NOT NULL,
                entity_type VARCHAR(100),
                entity_id UUID,
                content TEXT,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.comments\n";
        }
        
        // budgets table (26 failures)
        if (!Schema::hasTable('cmis.budgets')) {
            DB::statement("CREATE TABLE cmis.budgets (
                budget_id UUID PRIMARY KEY,
                org_id UUID NOT NULL,
                name VARCHAR(255),
                amount DECIMAL(10,2),
                currency VARCHAR(3) DEFAULT 'USD',
                period VARCHAR(50),
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.budgets\n";
        }
        
        // audit_logs table (26 failures)
        if (!Schema::hasTable('cmis.audit_logs')) {
            DB::statement("CREATE TABLE cmis.audit_logs (
                audit_id UUID PRIMARY KEY,
                org_id UUID,
                user_id UUID,
                action VARCHAR(100),
                entity_type VARCHAR(100),
                entity_id UUID,
                changes JSONB,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT now()
            )");
            echo "✓ Created table: cmis.audit_logs\n";
        }
        
        echo "\n✅ Migration complete! Created 20 missing tables.\n";
    }

    public function down(): void
    {
        DB::statement('SET search_path TO cmis,public');
        
        $tables = [
            'audit_logs', 'budgets', 'comments', 'custom_fields', 'posts', 'templates',
            'audiences', 'invoices', 'assets', 'platform_connections', 'content',
            'offerings', 'content_plan_items', 'api_logs', 'subscriptions',
            'knowledge_index', 'contacts', 'webhooks', 'leads', 'team_members'
        ];
        
        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS cmis.{$table} CASCADE");
        }
    }
};