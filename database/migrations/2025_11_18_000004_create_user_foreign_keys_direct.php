<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Domain: Database Integrity - User Foreign Keys (Direct Creation)
 *
 * Description: Directly create foreign key constraints for user reference columns
 * using raw SQL to bypass Laravel transaction/visibility issues.
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
        $pdo = DB::connection()->getPdo();

        echo "\n📊 Creating foreign keys directly...\n\n";

        // User reference foreign keys
        $userForeignKeys = [
            // user_id columns
            ['table' => 'user_permissions', 'column' => 'user_id', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_user_permissions_user', 'onDelete' => 'CASCADE'],
            ['table' => 'user_activities', 'column' => 'user_id', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_user_activities_user', 'onDelete' => 'CASCADE'],
            ['table' => 'user_sessions', 'column' => 'user_id', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_user_sessions_user', 'onDelete' => 'CASCADE'],
            ['table' => 'sessions', 'column' => 'user_id', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_sessions_user', 'onDelete' => 'SET NULL'],
            ['table' => 'scheduled_social_posts', 'column' => 'user_id', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_scheduled_social_posts_user', 'onDelete' => 'CASCADE'],
            ['table' => 'security_context_audit', 'column' => 'user_id', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_security_context_audit_user', 'onDelete' => 'SET NULL'],
            ['table' => 'notifications', 'column' => 'user_id', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_notifications_user', 'onDelete' => 'CASCADE'],

            // created_by columns
            ['table' => 'audience_templates', 'column' => 'created_by', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_audience_templates_created_by', 'onDelete' => 'SET NULL'],
            ['table' => 'campaign_context_links', 'column' => 'created_by', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_campaign_context_links_created_by', 'onDelete' => 'SET NULL'],
            ['table' => 'campaigns', 'column' => 'created_by', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_campaigns_created_by', 'onDelete' => 'SET NULL'],
            ['table' => 'integrations', 'column' => 'created_by', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_integrations_created_by', 'onDelete' => 'SET NULL'],
            ['table' => 'roles', 'column' => 'created_by', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_roles_created_by', 'onDelete' => 'SET NULL'],

            // updated_by columns
            ['table' => 'campaign_context_links', 'column' => 'updated_by', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_campaign_context_links_updated_by', 'onDelete' => 'SET NULL'],
            ['table' => 'integrations', 'column' => 'updated_by', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_integrations_updated_by', 'onDelete' => 'SET NULL'],

            // invited_by columns
            ['table' => 'team_invitations', 'column' => 'invited_by', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_team_invitations_invited_by', 'onDelete' => 'SET NULL'],
            ['table' => 'user_orgs', 'column' => 'invited_by', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_user_orgs_invited_by', 'onDelete' => 'SET NULL'],
        ];

        // Org reference foreign keys
        $orgForeignKeys = [
            ['table' => 'notifications', 'column' => 'org_id', 'refTable' => 'orgs', 'refColumn' => 'org_id', 'name' => 'fk_notifications_org', 'onDelete' => 'CASCADE'],
        ];

        $foreignKeys = array_merge($userForeignKeys, $orgForeignKeys);

        $added = 0;
        $skipped = 0;

        foreach ($foreignKeys as $fk) {
            try {
                // Check if table and column exist
                $tableCheck = $pdo->query("
                    SELECT 1 FROM information_schema.tables
                    WHERE table_schema = 'cmis' AND table_name = '{$fk['table']}'
                ");

                if (!$tableCheck || !$tableCheck->fetch()) {
                    echo "⚠️  Table cmis.{$fk['table']} does not exist, skipping\n";
                    $skipped++;
                    continue;
                }

                // Check if constraint already exists
                $constraintCheck = $pdo->query("
                    SELECT 1 FROM information_schema.table_constraints
                    WHERE constraint_schema = 'cmis'
                    AND table_name = '{$fk['table']}'
                    AND constraint_name = '{$fk['name']}'
                ");

                if ($constraintCheck && $constraintCheck->fetch()) {
                    echo "✓ FK {$fk['name']} already exists\n";
                    $skipped++;
                    continue;
                }

                // Create the foreign key
                $sql = "ALTER TABLE cmis.{$fk['table']}
                        ADD CONSTRAINT {$fk['name']}
                        FOREIGN KEY ({$fk['column']})
                        REFERENCES cmis.{$fk['refTable']}({$fk['refColumn']})
                        ON DELETE {$fk['onDelete']}";

                $pdo->exec($sql);
                echo "✓ Added FK: {$fk['name']} on cmis.{$fk['table']}.{$fk['column']} -> cmis.{$fk['refTable']}.{$fk['refColumn']}\n";
                $added++;

            } catch (\PDOException $e) {
                echo "⚠️  Could not add FK {$fk['name']}: " . substr($e->getMessage(), 0, 80) . "...\n";
                $skipped++;
            }
        }

        echo "\n✅ Foreign key creation complete!\n";
        echo "   Added: {$added}\n";
        echo "   Skipped: {$skipped}\n\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $pdo = DB::connection()->getPdo();

        $constraints = [
            ['table' => 'user_permissions', 'name' => 'fk_user_permissions_user'],
            ['table' => 'user_activities', 'name' => 'fk_user_activities_user'],
            ['table' => 'user_sessions', 'name' => 'fk_user_sessions_user'],
            ['table' => 'sessions', 'name' => 'fk_sessions_user'],
            ['table' => 'scheduled_social_posts', 'name' => 'fk_scheduled_social_posts_user'],
            ['table' => 'security_context_audit', 'name' => 'fk_security_context_audit_user'],
            ['table' => 'notifications', 'name' => 'fk_notifications_user'],
            ['table' => 'notifications', 'name' => 'fk_notifications_org'],
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

        foreach ($constraints as $constraint) {
            try {
                $pdo->exec("ALTER TABLE cmis.{$constraint['table']} DROP CONSTRAINT IF EXISTS {$constraint['name']}");
            } catch (\PDOException $e) {
                // Ignore errors on down
            }
        }
    }
};
