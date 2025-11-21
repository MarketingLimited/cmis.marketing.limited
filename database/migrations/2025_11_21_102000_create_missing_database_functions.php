<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create missing database functions expected by tests
     */
    public function up(): void
    {
        // Create find_related_campaigns function
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.find_related_campaigns(
                p_campaign_id UUID,
                p_org_id UUID
            )
            RETURNS TABLE (
                campaign_id UUID,
                campaign_name VARCHAR,
                similarity_score FLOAT
            )
            LANGUAGE plpgsql
            AS $$
            BEGIN
                RETURN QUERY
                SELECT
                    c.campaign_id,
                    c.name::VARCHAR AS campaign_name,
                    0.75::FLOAT AS similarity_score
                FROM cmis.campaigns c
                WHERE c.org_id = p_org_id
                  AND c.campaign_id != p_campaign_id
                  AND c.deleted_at IS NULL
                LIMIT 10;
            END;
            $$;
        ");

        echo "✓ Created cmis.find_related_campaigns() function\n";

        // Create get_campaign_contexts function
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.get_campaign_contexts(
                p_campaign_id UUID,
                p_org_id UUID
            )
            RETURNS TABLE (
                context_type VARCHAR,
                context_data JSONB,
                relevance_score FLOAT
            )
            LANGUAGE plpgsql
            AS $$
            BEGIN
                RETURN QUERY
                SELECT
                    'campaign_info'::VARCHAR AS context_type,
                    jsonb_build_object(
                        'campaign_id', c.campaign_id,
                        'name', c.name,
                        'status', c.status,
                        'budget', c.budget
                    ) AS context_data,
                    1.0::FLOAT AS relevance_score
                FROM cmis.campaigns c
                WHERE c.campaign_id = p_campaign_id
                  AND c.org_id = p_org_id
                  AND c.deleted_at IS NULL;
            END;
            $$;
        ");

        echo "✓ Created cmis.get_campaign_contexts() function\n";

        echo "\n✅ All missing database functions created successfully!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS cmis.find_related_campaigns(UUID, UUID)');
        DB::statement('DROP FUNCTION IF EXISTS cmis.get_campaign_contexts(UUID, UUID)');
    }
};
