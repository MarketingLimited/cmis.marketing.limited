<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Integrity - User Reference Foreign Keys
 *
 * Description: Add foreign key constraints for all user reference columns
 * (created_by, updated_by, invited_by, user_id) to ensure referential integrity.
 *
 * This migration checks for existing constraints and only adds missing ones.
 */
return new class extends Migration
{
    /**
     * Disable transactions for this migration to avoid cascading failures
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::connection()->getPdo()->exec('SET client_min_messages TO WARNING');

        echo "\n📊 Adding foreign key constraints for user reference columns...\n\n";

        // Define all foreign key constraints to be added
        $foreignKeys = [
            // user_id columns
            ['schema' => 'cmis', 'table' => 'user_permissions', 'column' => 'user_id', 'name' => 'fk_user_permissions_user'],
            ['schema' => 'cmis', 'table' => 'user_activities', 'column' => 'user_id', 'name' => 'fk_user_activities_user'],
            ['schema' => 'cmis', 'table' => 'user_sessions', 'column' => 'user_id', 'name' => 'fk_user_sessions_user'],
            ['schema' => 'cmis', 'table' => 'sessions', 'column' => 'user_id', 'name' => 'fk_sessions_user'],
            ['schema' => 'cmis', 'table' => 'scheduled_social_posts', 'column' => 'user_id', 'name' => 'fk_scheduled_social_posts_user'],
            ['schema' => 'cmis', 'table' => 'security_context_audit', 'column' => 'user_id', 'name' => 'fk_security_context_audit_user'],

            // created_by columns
            ['schema' => 'cmis', 'table' => 'audience_templates', 'column' => 'created_by', 'name' => 'fk_audience_templates_created_by'],
            ['schema' => 'cmis', 'table' => 'campaign_context_links', 'column' => 'created_by', 'name' => 'fk_campaign_context_links_created_by'],
            ['schema' => 'cmis', 'table' => 'campaigns', 'column' => 'created_by', 'name' => 'fk_campaigns_created_by'],
            ['schema' => 'cmis', 'table' => 'integrations', 'column' => 'created_by', 'name' => 'fk_integrations_created_by'],
            ['schema' => 'cmis', 'table' => 'roles', 'column' => 'created_by', 'name' => 'fk_roles_created_by'],

            // updated_by columns
            ['schema' => 'cmis', 'table' => 'campaign_context_links', 'column' => 'updated_by', 'name' => 'fk_campaign_context_links_updated_by'],
            ['schema' => 'cmis', 'table' => 'integrations', 'column' => 'updated_by', 'name' => 'fk_integrations_updated_by'],

            // invited_by columns
            ['schema' => 'cmis', 'table' => 'team_invitations', 'column' => 'invited_by', 'name' => 'fk_team_invitations_invited_by'],
            ['schema' => 'cmis', 'table' => 'user_orgs', 'column' => 'invited_by', 'name' => 'fk_user_orgs_invited_by'],
        ];

        $added = 0;
        $skipped = 0;

        foreach ($foreignKeys as $fk) {
            // Check if table exists
            $tableExists = DB::selectOne("
                SELECT 1
                FROM information_schema.tables
                WHERE table_schema = ?
                AND table_name = ?
            ", [$fk['schema'], $fk['table']]);

            if (!$tableExists) {
                echo "⚠️  Table {$fk['schema']}.{$fk['table']} does not exist, skipping...\n";
                $skipped++;
                continue;
            }

            // Check if column exists
            $columnExists = DB::selectOne("
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = ?
                AND table_name = ?
                AND column_name = ?
            ", [$fk['schema'], $fk['table'], $fk['column']]);

            if (!$columnExists) {
                echo "⚠️  Column {$fk['schema']}.{$fk['table']}.{$fk['column']} does not exist, skipping...\n";
                $skipped++;
                continue;
            }

            // Check if constraint already exists
            $exists = DB::selectOne("
                SELECT 1
                FROM information_schema.table_constraints
                WHERE constraint_schema = ?
                AND table_name = ?
                AND constraint_name = ?
                AND constraint_type = 'FOREIGN KEY'
            ", [$fk['schema'], $fk['table'], $fk['name']]);

            if ($exists) {
                echo "✓ FK {$fk['name']} already exists, skipping...\n";
                $skipped++;
                continue;
            }

            // Check if there's already a FK on this column to users table
            $existingFk = DB::selectOne("
                SELECT tc.constraint_name
                FROM information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                    ON tc.constraint_name = kcu.constraint_name
                    AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage AS ccu
                    ON ccu.constraint_name = tc.constraint_name
                    AND ccu.table_schema = tc.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY'
                AND tc.table_schema = ?
                AND tc.table_name = ?
                AND kcu.column_name = ?
                AND ccu.table_name = 'users'
            ", [$fk['schema'], $fk['table'], $fk['column']]);

            if ($existingFk) {
                echo "✓ FK already exists on {$fk['schema']}.{$fk['table']}.{$fk['column']} as {$existingFk->constraint_name}, skipping...\n";
                $skipped++;
                continue;
            }

            try {
                // Add the foreign key constraint using ON DELETE SET NULL for audit columns
                // and ON DELETE CASCADE for critical relationships
                $onDelete = in_array($fk['column'], ['created_by', 'updated_by', 'invited_by'])
                    ? 'SET NULL'
                    : 'CASCADE';

                DB::unprepared("
                    ALTER TABLE {$fk['schema']}.{$fk['table']}
                    ADD CONSTRAINT {$fk['name']}
                    FOREIGN KEY ({$fk['column']})
                    REFERENCES cmis.users(user_id)
                    ON DELETE {$onDelete}
                ");

                echo "✓ Added FK: {$fk['name']} on {$fk['schema']}.{$fk['table']}.{$fk['column']}\n";
                $added++;
            } catch (\Exception $e) {
                echo "⚠️  Could not add FK {$fk['name']}: " . substr($e->getMessage(), 0, 100) . "...\n";
                $skipped++;
            }
        }

        echo "\n✅ Foreign key migration complete!\n";
        echo "   Added: {$added}\n";
        echo "   Skipped: {$skipped}\n\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $foreignKeys = [
            ['schema' => 'cmis', 'table' => 'user_permissions', 'name' => 'fk_user_permissions_user'],
            ['schema' => 'cmis', 'table' => 'user_activities', 'name' => 'fk_user_activities_user'],
            ['schema' => 'cmis', 'table' => 'user_sessions', 'name' => 'fk_user_sessions_user'],
            ['schema' => 'cmis', 'table' => 'sessions', 'name' => 'fk_sessions_user'],
            ['schema' => 'cmis', 'table' => 'scheduled_social_posts', 'name' => 'fk_scheduled_social_posts_user'],
            ['schema' => 'cmis', 'table' => 'security_context_audit', 'name' => 'fk_security_context_audit_user'],
            ['schema' => 'cmis', 'table' => 'audience_templates', 'name' => 'fk_audience_templates_created_by'],
            ['schema' => 'cmis', 'table' => 'campaign_context_links', 'name' => 'fk_campaign_context_links_created_by'],
            ['schema' => 'cmis', 'table' => 'campaigns', 'name' => 'fk_campaigns_created_by'],
            ['schema' => 'cmis', 'table' => 'integrations', 'name' => 'fk_integrations_created_by'],
            ['schema' => 'cmis', 'table' => 'roles', 'name' => 'fk_roles_created_by'],
            ['schema' => 'cmis', 'table' => 'campaign_context_links', 'name' => 'fk_campaign_context_links_updated_by'],
            ['schema' => 'cmis', 'table' => 'integrations', 'name' => 'fk_integrations_updated_by'],
            ['schema' => 'cmis', 'table' => 'team_invitations', 'name' => 'fk_team_invitations_invited_by'],
            ['schema' => 'cmis', 'table' => 'user_orgs', 'name' => 'fk_user_orgs_invited_by'],
        ];

        foreach ($foreignKeys as $fk) {
            try {
                DB::unprepared("ALTER TABLE {$fk['schema']}.{$fk['table']} DROP CONSTRAINT IF EXISTS {$fk['name']}");
            } catch (\Exception $e) {
                // Ignore errors on down migration
            }
        }
    }
};
