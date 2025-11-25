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
        echo "\n📊 Creating foreign keys using DO blocks...\n\n";

        // User reference foreign keys
        $userForeignKeys = [
            // user_id columns
            ['table' => 'user_permissions', 'column' => 'user_id', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_user_permissions_user', 'onDelete' => 'CASCADE'],
            ['table' => 'user_activities', 'column' => 'user_id', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_user_activities_user', 'onDelete' => 'CASCADE'],
            ['table' => 'user_sessions', 'column' => 'user_id', 'refTable' => 'users', 'refColumn' => 'user_id', 'name' => 'fk_user_sessions_user', 'onDelete' => 'CASCADE'],
            // NOTE: sessions FK is handled by 2025_11_25_210758_fix_sessions_user_id_data_type.php after datatype fix
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
                // Use DO block to conditionally create foreign key
                $sql = "
                    DO $$
                    BEGIN
                        -- Check if table exists
                        IF NOT EXISTS (
                            SELECT 1 FROM information_schema.tables
                            WHERE table_schema = 'cmis' AND table_name = '{$fk['table']}'
                        ) THEN
                            RAISE NOTICE 'Table cmis.{$fk['table']} does not exist, skipping';
                            RETURN;
                        END IF;

                        -- Check if constraint already exists
                        IF NOT EXISTS (
                            SELECT 1 FROM information_schema.table_constraints
                            WHERE constraint_schema = 'cmis'
                            AND table_name = '{$fk['table']}'
                            AND constraint_name = '{$fk['name']}'
                        ) THEN
                            -- Create the foreign key
                            EXECUTE 'ALTER TABLE cmis.{$fk['table']}
                                     ADD CONSTRAINT {$fk['name']}
                                     FOREIGN KEY ({$fk['column']})
                                     REFERENCES cmis.{$fk['refTable']}({$fk['refColumn']})
                                     ON DELETE {$fk['onDelete']}';
                            RAISE NOTICE 'Added FK: {$fk['name']}';
                        ELSE
                            RAISE NOTICE 'FK {$fk['name']} already exists';
                        END IF;
                    END
                    $$;
                ";

                DB::statement($sql);

                // Count based on whether the constraint was added or skipped
                $checkResult = DB::selectOne("
                    SELECT 1 as exists FROM information_schema.table_constraints
                    WHERE constraint_schema = 'cmis'
                    AND table_name = '{$fk['table']}'
                    AND constraint_name = '{$fk['name']}'
                ");

                if ($checkResult) {
                    echo "✓ FK {$fk['name']} on cmis.{$fk['table']}.{$fk['column']} -> cmis.{$fk['refTable']}.{$fk['refColumn']}\n";
                    $added++;
                } else {
                    $skipped++;
                }

            } catch (\Exception $e) {
                echo "⚠️  Error processing FK {$fk['name']}: " . $e->getMessage() . "\n";
                $skipped++;
            }
        }

        echo "\n✅ Foreign key creation complete!\n";
        echo "   Processed: {$added}\n";
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
            // NOTE: sessions FK is handled by 2025_11_25_210758_fix_sessions_user_id_data_type.php
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
