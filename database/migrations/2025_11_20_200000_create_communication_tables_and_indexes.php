<?php

use Illuminate\Database\Migrations\Migration;
use Database\Migrations\Concerns\HasRLSPolicies;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Domain: Communication Services
 *
 * Description: Create SMS, email, and notification tables with performance indexes
 *
 * AI Agent Context: Supports EmailService, SMSService, and NotificationRepository
 * Sprint: Phase 3 - P3 Performance Optimization
 */
return new class extends Migration
{
    use HasRLSPolicies;

    public function up(): void
    {
        // Create SMS Log table
        if (!Schema::hasTable('cmis.sms_log')) {
            DB::statement("
                CREATE TABLE cmis.sms_log (
                    log_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL,
                    message_id VARCHAR(255),
                    to_phone VARCHAR(50) NOT NULL,
                    from_phone VARCHAR(50),
                    message TEXT NOT NULL,
                    provider VARCHAR(50) NOT NULL,
                    status VARCHAR(50) NOT NULL DEFAULT 'sent',
                    sent_at TIMESTAMP NOT NULL,
                    delivery_status VARCHAR(50),
                    delivered_at TIMESTAMP,
                    error_message TEXT,
                    metadata JSONB,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Add RLS policy
            
            $this->enableRLS('cmis.sms_log');
        }

        // Create Scheduled SMS table
        if (!Schema::hasTable('cmis.scheduled_sms')) {
            DB::statement("
                CREATE TABLE cmis.scheduled_sms (
                    schedule_id VARCHAR(255) PRIMARY KEY,
                    org_id UUID NOT NULL,
                    to_phone VARCHAR(50) NOT NULL,
                    message TEXT NOT NULL,
                    scheduled_at TIMESTAMP NOT NULL,
                    status VARCHAR(50) NOT NULL DEFAULT 'pending',
                    sent_at TIMESTAMP,
                    error_message TEXT,
                    retry_count INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Add RLS policy
            
            $this->enableRLS('cmis.scheduled_sms');
        }

        // Create SMS Templates table
        if (!Schema::hasTable('cmis.sms_templates')) {
            DB::statement("
                CREATE TABLE cmis.sms_templates (
                    template_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL,
                    template_name VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    description TEXT,
                    variables JSONB,
                    is_active BOOLEAN NOT NULL DEFAULT true,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    deleted_at TIMESTAMP
                )
            ");

            // Add RLS policy
            
            $this->enableRLS('cmis.sms_templates');
        }

        // Create Notifications table if not exists
        if (!Schema::hasTable('cmis.notifications')) {
            DB::statement("
                CREATE TABLE cmis.notifications (
                    notification_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    org_id UUID NOT NULL,
                    user_id UUID NOT NULL,
                    type VARCHAR(100) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    data JSONB,
                    read BOOLEAN NOT NULL DEFAULT false,
                    read_at TIMESTAMP,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Note: RLS policy already enabled in 2025_11_18_000003_create_notifications_table.php
            // Removed duplicate enableRLS('cmis.notifications') call to prevent policy conflicts
        }

        // Create Performance Indexes

        // SMS Log indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_sms_log_org_sent ON cmis.sms_log(org_id, sent_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_sms_log_to_phone ON cmis.sms_log(to_phone)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_sms_log_provider_status ON cmis.sms_log(provider, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_sms_log_status ON cmis.sms_log(status) WHERE status != \'sent\'');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_sms_log_message_id ON cmis.sms_log(message_id) WHERE message_id IS NOT NULL');

        // Scheduled SMS indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_scheduled_sms_org_scheduled ON cmis.scheduled_sms(org_id, scheduled_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_scheduled_sms_status_scheduled ON cmis.scheduled_sms(status, scheduled_at) WHERE status = \'pending\'');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_scheduled_sms_status ON cmis.scheduled_sms(status)');

        // SMS Templates indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_sms_templates_org_name ON cmis.sms_templates(org_id, template_name) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_sms_templates_active ON cmis.sms_templates(org_id, is_active) WHERE is_active = true AND deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uniq_sms_templates_org_name ON cmis.sms_templates(org_id, template_name) WHERE deleted_at IS NULL');

        // Notifications indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_notifications_user_created ON cmis.notifications(user_id, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_notifications_user_unread_new ON cmis.notifications(user_id, read) WHERE read = false');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_notifications_type ON cmis.notifications(type)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_notifications_org_type ON cmis.notifications(org_id, type, created_at DESC)');

        // Additional indexes for existing tables that may benefit from optimization
        // These are wrapped in checks to ensure tables exist before creating indexes

        // Social Posts - for BulkPostService queries
        if (Schema::hasTable('cmis.social_posts') && Schema::hasColumn('cmis.social_posts', 'platform')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_social_posts_platform_status ON cmis.social_posts(platform, status) WHERE deleted_at IS NULL');
        }
        if (Schema::hasTable('cmis.social_posts') && Schema::hasColumn('cmis.social_posts', 'engagement_rate')) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_social_posts_engagement ON cmis.social_posts(org_id, engagement_rate DESC NULLS LAST) WHERE status = \'published\' AND deleted_at IS NULL');
        }

        // Publishing Queue - for scheduling operations
        if (Schema::hasTable('cmis.publishing_queues') && Schema::hasColumns('cmis.publishing_queues', ['status', 'social_account_id', 'scheduled_for'])) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_publishing_queue_scheduled ON cmis.publishing_queues(social_account_id, scheduled_for) WHERE status = \'scheduled\'');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_publishing_queue_status_time ON cmis.publishing_queues(status, scheduled_for)');
        }

        // Integrations - for platform authentication lookups
        if (Schema::hasTable('cmis.integrations') && Schema::hasColumns('cmis.integrations', ['org_id', 'provider', 'status', 'is_active', 'deleted_at'])) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_integrations_org_provider ON cmis.integrations(org_id, provider, status) WHERE deleted_at IS NULL');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_integrations_provider_active ON cmis.integrations(provider, is_active) WHERE is_active = true AND deleted_at IS NULL');
        }

        // User Organizations - for permission checks
        if (Schema::hasTable('cmis.user_orgs') && Schema::hasColumns('cmis.user_orgs', ['user_id', 'org_id'])) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_user_orgs_user_org ON cmis.user_orgs(user_id, org_id)');
        }
        if (Schema::hasTable('cmis.user_orgs') && Schema::hasColumns('cmis.user_orgs', ['org_id', 'role_id', 'deleted_at'])) {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_user_orgs_org_role ON cmis.user_orgs(org_id, role_id) WHERE deleted_at IS NULL');
        }
    }

    public function down(): void
    {
        // Drop indexes
        DB::statement('DROP INDEX IF EXISTS cmis.idx_sms_log_org_sent');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_sms_log_to_phone');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_sms_log_provider_status');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_sms_log_status');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_sms_log_message_id');

        DB::statement('DROP INDEX IF EXISTS cmis.idx_scheduled_sms_org_scheduled');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_scheduled_sms_status_scheduled');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_scheduled_sms_status');

        DB::statement('DROP INDEX IF EXISTS cmis.idx_sms_templates_org_name');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_sms_templates_active');
        DB::statement('DROP INDEX IF EXISTS cmis.uniq_sms_templates_org_name');

        DB::statement('DROP INDEX IF EXISTS cmis.idx_notifications_user_created');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_notifications_user_unread_new');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_notifications_type');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_notifications_org_type');

        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_platform_status');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_social_posts_engagement');

        DB::statement('DROP INDEX IF EXISTS cmis.idx_publishing_queue_scheduled');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_publishing_queue_status_time');

        DB::statement('DROP INDEX IF EXISTS cmis.idx_integrations_org_provider');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_integrations_provider_active');

        DB::statement('DROP INDEX IF EXISTS cmis.idx_user_orgs_user_org');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_user_orgs_org_role');

        // Drop tables
        Schema::dropIfExists('cmis.notifications');
        Schema::dropIfExists('cmis.sms_templates');
        Schema::dropIfExists('cmis.scheduled_sms');
        Schema::dropIfExists('cmis.sms_log');
    }
};
