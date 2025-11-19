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
        // Add updated_at column to all tables that have created_at but not updated_at
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
            // Check if table exists and doesn't already have updated_at
            $hasColumn = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1
                    FROM information_schema.columns
                    WHERE table_schema = 'cmis'
                    AND table_name = ?
                    AND column_name = 'updated_at'
                ) as exists
            ", [$table]);

            if (!$hasColumn->exists) {
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
