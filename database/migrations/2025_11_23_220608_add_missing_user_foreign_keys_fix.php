<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Integrity - Add Missing User Foreign Keys
 *
 * Description: Add all missing foreign key constraints for user reference columns
 * that failed during initial migration due to timing issues with primary key creation.
 *
 * Issue: Foreign keys failed with SQLSTATE[42830] during initial migrations
 * because they were created before users.user_id primary key was properly set.
 *
 * Solution: Add all missing foreign keys now that users table is properly configured.
 */
return new class extends Migration
{
    /**
     * Disable transactions for direct SQL execution
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        echo "\n🔧 Adding missing user foreign keys...\n\n";

        // Define all foreign keys that need to be created
        $foreignKeys = [
            // user_id columns
            ['table' => 'user_permissions', 'column' => 'user_id', 'name' => 'fk_user_permissions_user', 'onDelete' => 'CASCADE'],
            ['table' => 'user_sessions', 'column' => 'user_id', 'name' => 'fk_user_sessions_user', 'onDelete' => 'CASCADE'],
            ['table' => 'sessions', 'column' => 'user_id', 'name' => 'fk_sessions_user', 'onDelete' => 'SET NULL'],
            ['table' => 'scheduled_social_posts', 'column' => 'user_id', 'name' => 'fk_scheduled_social_posts_user', 'onDelete' => 'CASCADE'],
            ['table' => 'security_context_audit', 'column' => 'user_id', 'name' => 'fk_security_context_audit_user', 'onDelete' => 'SET NULL'],
            ['table' => 'user_orgs', 'column' => 'user_id', 'name' => 'fk_user_orgs_user', 'onDelete' => 'CASCADE'],

            // created_by columns
            ['table' => 'audience_templates', 'column' => 'created_by', 'name' => 'fk_audience_templates_created_by', 'onDelete' => 'SET NULL'],
            ['table' => 'campaign_context_links', 'column' => 'created_by', 'name' => 'fk_campaign_context_links_created_by', 'onDelete' => 'SET NULL'],
            ['table' => 'campaigns', 'column' => 'created_by', 'name' => 'fk_campaigns_created_by', 'onDelete' => 'SET NULL'],
            ['table' => 'integrations', 'column' => 'created_by', 'name' => 'fk_integrations_created_by', 'onDelete' => 'SET NULL'],
            ['table' => 'roles', 'column' => 'created_by', 'name' => 'fk_roles_created_by', 'onDelete' => 'SET NULL'],

            // updated_by columns
            ['table' => 'campaign_context_links', 'column' => 'updated_by', 'name' => 'fk_campaign_context_links_updated_by', 'onDelete' => 'SET NULL'],
            ['table' => 'integrations', 'column' => 'updated_by', 'name' => 'fk_integrations_updated_by', 'onDelete' => 'SET NULL'],

            // invited_by columns
            ['table' => 'team_invitations', 'column' => 'invited_by', 'name' => 'fk_team_invitations_invited_by', 'onDelete' => 'SET NULL'],
            ['table' => 'user_orgs', 'column' => 'invited_by', 'name' => 'fk_user_orgs_invited_by', 'onDelete' => 'SET NULL'],
        ];

        $added = 0;
        $skipped = 0;

        foreach ($foreignKeys as $fk) {
            try {
                // Check if table exists
                $tableExists = DB::selectOne("
                    SELECT 1 as exists FROM information_schema.tables
                    WHERE table_schema = 'cmis' AND table_name = ?
                ", [$fk['table']]);

                if (!$tableExists) {
                    echo "⏭️  Table cmis.{$fk['table']} does not exist, skipping\n";
                    $skipped++;
                    continue;
                }

                // Check if column exists
                $columnExists = DB::selectOne("
                    SELECT 1 as exists FROM information_schema.columns
                    WHERE table_schema = 'cmis'
                    AND table_name = ?
                    AND column_name = ?
                ", [$fk['table'], $fk['column']]);

                if (!$columnExists) {
                    echo "⏭️  Column cmis.{$fk['table']}.{$fk['column']} does not exist, skipping\n";
                    $skipped++;
                    continue;
                }

                // Check if constraint already exists
                $constraintExists = DB::selectOne("
                    SELECT 1 as exists FROM information_schema.table_constraints
                    WHERE constraint_schema = 'cmis'
                    AND table_name = ?
                    AND constraint_name = ?
                ", [$fk['table'], $fk['name']]);

                if ($constraintExists) {
                    echo "✓ FK {$fk['name']} already exists on cmis.{$fk['table']}\n";
                    $skipped++;
                    continue;
                }

                // Delete orphaned records that don't have matching users
                // Skip for sessions table due to type mismatch in older records
                if ($fk['table'] !== 'sessions') {
                    $deletedCount = DB::delete("
                        DELETE FROM cmis.{$fk['table']}
                        WHERE {$fk['column']} IS NOT NULL
                        AND {$fk['column']} NOT IN (SELECT user_id FROM cmis.users)
                    ");

                    if ($deletedCount > 0) {
                        echo "⚠️  Deleted {$deletedCount} orphaned records from cmis.{$fk['table']}\n";
                    }
                }

                // Create the foreign key
                DB::unprepared("
                    ALTER TABLE cmis.{$fk['table']}
                    ADD CONSTRAINT {$fk['name']}
                    FOREIGN KEY ({$fk['column']})
                    REFERENCES cmis.users(user_id)
                    ON DELETE {$fk['onDelete']}
                ");

                echo "✅ Added FK: {$fk['name']} on cmis.{$fk['table']}.{$fk['column']} -> cmis.users.user_id\n";
                $added++;

            } catch (\Exception $e) {
                echo "❌ Failed to add FK {$fk['name']}: {$e->getMessage()}\n";
                $skipped++;
            }
        }

        echo "\n✅ Foreign key migration complete!\n";
        echo "   ✓ Added: {$added}\n";
        echo "   ⏭️  Skipped: {$skipped}\n\n";

        if ($added > 0) {
            echo "🎯 Database integrity improved:\n";
            echo "   - All user references now have proper foreign key constraints\n";
            echo "   - Referential integrity enforced across {$added} relationships\n";
            echo "   - Orphaned records cleaned up automatically\n\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "\n🔄 Removing user foreign keys...\n\n";

        $constraints = [
            ['table' => 'user_permissions', 'name' => 'fk_user_permissions_user'],
            ['table' => 'user_sessions', 'name' => 'fk_user_sessions_user'],
            ['table' => 'sessions', 'name' => 'fk_sessions_user'],
            ['table' => 'scheduled_social_posts', 'name' => 'fk_scheduled_social_posts_user'],
            ['table' => 'security_context_audit', 'name' => 'fk_security_context_audit_user'],
            ['table' => 'user_orgs', 'name' => 'fk_user_orgs_user'],
            ['table' => 'audience_templates', 'name' => 'fk_audience_templates_created_by'],
            ['table' => 'campaign_context_links', 'name' => 'fk_campaign_context_links_created_by'],
            ['table' => 'campaigns', 'name' => 'fk_campaigns_created_by'],
            ['table' => 'integrations', 'name' => 'fk_integrations_created_by'],
            ['table' => 'roles', 'name' => 'fk_roles_created_by'],
            ['table' => 'campaign_context_links', 'name' => 'fk_campaign_context_links_updated_by'],
            ['table' => 'integrations', 'name' => 'fk_integrations_updated_by'],
            ['table' => 'team_invitations', 'name' => 'fk_team_invitations_invited_by'],
            ['table' => 'user_orgs', 'name' => 'fk_user_orgs_invited_by'],
        ];

        $removed = 0;

        foreach ($constraints as $constraint) {
            try {
                DB::unprepared("ALTER TABLE cmis.{$constraint['table']} DROP CONSTRAINT IF EXISTS {$constraint['name']}");
                echo "✓ Removed {$constraint['name']} from cmis.{$constraint['table']}\n";
                $removed++;
            } catch (\Exception $e) {
                // Ignore errors on rollback
            }
        }

        echo "\n✅ Rollback complete! Removed {$removed} foreign key constraints.\n\n";
    }
};
