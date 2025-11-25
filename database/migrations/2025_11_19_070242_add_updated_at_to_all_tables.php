<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add both created_at and updated_at columns to all tables that need them
        $tables = [
            'orgs',
            'user_orgs',
            'org_markets',
            'ad_campaigns',
            'ad_metrics',
            'ai_actions',
            'ai_generated_campaigns',
            'ai_models',
            'cognitive_tracker_template',
            'cognitive_trends',
            'compliance_audits',
            'content_plans',
            'contexts',
            'contexts_base',
            'copy_components',
            'creative_assets',
            'creative_briefs',
            'experiments',
            'export_bundles',
            'field_definitions',
            'field_values',
            'job_batches',
            'jobs',
            'meta_documentation',
            'meta_field_dictionary',
            'meta_function_descriptions',
            'offerings_full_details',
            'offerings_old',
            'password_reset_tokens',
            'predictive_visual_engine',
            'reference_entities',
            'roles',
            'security_context_audit',
            'social_post_metrics',
            'social_posts',
            'team_account_access',
            'user_activities',
            'user_sessions',
            'value_contexts',
            'permissions',
            'role_permissions',
            'user_permissions',
            'social_accounts',
            'team_invitations',
            'sessions',
        ];

        foreach ($tables as $table) {
            // Check if table exists first
            $tableExists = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1
                    FROM information_schema.tables
                    WHERE table_schema = 'cmis'
                    AND table_name = ?
                ) as exists
            ", [$table]);

            if (!$tableExists->exists) {
                continue; // Skip if table doesn't exist
            }

            // Check and add created_at if it doesn't exist
            $hasCreatedAt = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1
                    FROM information_schema.columns
                    WHERE table_schema = 'cmis'
                    AND table_name = ?
                    AND column_name = 'created_at'
                ) as exists
            ", [$table]);

            if (!$hasCreatedAt->exists) {
                DB::statement("
                    ALTER TABLE cmis.{$table}
                    ADD COLUMN created_at TIMESTAMP WITH TIME ZONE DEFAULT now()
                ");
                echo "✓ Added created_at to cmis.{$table}\n";
            }

            // Check and add updated_at if it doesn't exist
            $hasUpdatedAt = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1
                    FROM information_schema.columns
                    WHERE table_schema = 'cmis'
                    AND table_name = ?
                    AND column_name = 'updated_at'
                ) as exists
            ", [$table]);

            if (!$hasUpdatedAt->exists) {
                DB::statement("
                    ALTER TABLE cmis.{$table}
                    ADD COLUMN updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
                ");
                echo "✓ Added updated_at to cmis.{$table}\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'orgs',
            'ad_campaigns',
            'ad_metrics',
            'ai_actions',
            'ai_generated_campaigns',
            'ai_models',
            'cognitive_tracker_template',
            'cognitive_trends',
            'compliance_audits',
            'content_plans',
            'contexts',
            'contexts_base',
            'copy_components',
            'creative_assets',
            'creative_briefs',
            'experiments',
            'export_bundles',
            'field_definitions',
            'field_values',
            'job_batches',
            'jobs',
            'meta_documentation',
            'meta_field_dictionary',
            'meta_function_descriptions',
            'offerings_full_details',
            'offerings_old',
            'password_reset_tokens',
            'predictive_visual_engine',
            'reference_entities',
            'roles',
            'security_context_audit',
            'social_post_metrics',
            'social_posts',
            'team_account_access',
            'user_activities',
            'user_sessions',
            'value_contexts',
        ];

        foreach ($tables as $table) {
            DB::statement("
                ALTER TABLE cmis.{$table}
                DROP COLUMN IF EXISTS updated_at
            ");
        }
    }
};
