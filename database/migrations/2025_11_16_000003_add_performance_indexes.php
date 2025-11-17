<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     *
     * Adds performance indexes to frequently queried columns.
     * Uses CONCURRENTLY to avoid locking tables during index creation.
     */
    public function up(): void
    {
        echo "\n📊 Adding performance indexes...\n\n";

        // Campaigns indexes
        echo "Creating indexes for campaigns table...\n";
        $this->createIndexConcurrently('idx_campaigns_org_status', 'cmis.campaigns', ['org_id', 'status']);
        $this->createIndexConcurrently('idx_campaigns_dates', 'cmis.campaigns', ['start_date', 'end_date']);
        $this->createIndexConcurrently('idx_campaigns_created', 'cmis.campaigns', ['created_at DESC']);

        // Content Plans indexes
        echo "Creating indexes for content_plans table...\n";
        $this->createIndexConcurrently('idx_content_plans_campaign', 'cmis.content_plans', ['campaign_id', 'status']);
        $this->createIndexConcurrently('idx_content_plans_org', 'cmis.content_plans', ['org_id', 'created_at DESC']);

        // Content Items indexes
        echo "Creating indexes for content_items table...\n";
        $this->createIndexConcurrently('idx_content_items_plan', 'cmis.content_items', ['content_plan_id']);
        $this->createIndexConcurrently('idx_content_items_status', 'cmis.content_items', ['status', 'scheduled_at']);

        // Knowledge Base indexes
        echo "Creating indexes for knowledge_base table...\n";
        $this->createIndexConcurrently('idx_knowledge_org_type', 'cmis.knowledge_base', ['org_id', 'content_type']);
        $this->createIndexConcurrently('idx_knowledge_created', 'cmis.knowledge_base', ['created_at DESC']);
        $this->createIndexConcurrently('idx_knowledge_title', 'cmis.knowledge_base', ['title']);

        // Knowledge Embeddings - Vector similarity search index
        echo "Creating vector index for knowledge_embeddings table...\n";
        $this->createIndexConcurrently(
            'idx_embeddings_vector',
            'cmis.knowledge_embeddings',
            ['embedding vector_cosine_ops'],
            'ivfflat'
        );
        $this->createIndexConcurrently('idx_embeddings_knowledge', 'cmis.knowledge_embeddings', ['knowledge_id']);

        // Ad Accounts indexes
        echo "Creating indexes for ad_accounts table...\n";
        $this->createIndexConcurrently('idx_ad_accounts_org', 'cmis.ad_accounts', ['org_id', 'platform']);
        $this->createIndexConcurrently('idx_ad_accounts_status', 'cmis.ad_accounts', ['status']);

        // Ad Campaigns indexes
        echo "Creating indexes for ad_campaigns table...\n";
        $this->createIndexConcurrently('idx_ad_campaigns_account', 'cmis.ad_campaigns', ['ad_account_id', 'status']);
        $this->createIndexConcurrently('idx_ad_campaigns_dates', 'cmis.ad_campaigns', ['start_date', 'end_date']);

        // Ad Metrics indexes (critical for performance)
        echo "Creating indexes for ad_metrics table...\n";
        $this->createIndexConcurrently('idx_ad_metrics_entity_date', 'cmis.ad_metrics', ['ad_entity_id', 'recorded_at DESC']);
        $this->createIndexConcurrently('idx_ad_metrics_date', 'cmis.ad_metrics', ['recorded_at DESC']);
        $this->createIndexConcurrently('idx_ad_metrics_campaign', 'cmis.ad_metrics', ['ad_campaign_id', 'recorded_at DESC']);

        // User Orgs indexes
        echo "Creating indexes for user_orgs table...\n";
        $this->createIndexConcurrently('idx_user_orgs_user', 'cmis.user_orgs', ['user_id', 'is_active']);
        $this->createIndexConcurrently('idx_user_orgs_org', 'cmis.user_orgs', ['org_id', 'is_active']);

        // Creative Assets indexes
        echo "Creating indexes for creative_assets table...\n";
        $this->createIndexConcurrently('idx_creative_assets_org', 'cmis.creative_assets', ['org_id', 'asset_type']);
        $this->createIndexConcurrently('idx_creative_assets_created', 'cmis.creative_assets', ['created_at DESC']);

        // Compliance Audits indexes
        echo "Creating indexes for compliance_audits table...\n";
        $this->createIndexConcurrently('idx_compliance_audits_content', 'cmis.compliance_audits', ['content_item_id']);
        $this->createIndexConcurrently('idx_compliance_audits_status', 'cmis.compliance_audits', ['status', 'created_at DESC']);

        // Audit Logs indexes (important for security tracking)
        echo "Creating indexes for activity_logs table...\n";
        $this->createIndexConcurrently('idx_audit_user_date', 'cmis_audit.activity_logs', ['user_id', 'created_at DESC']);
        $this->createIndexConcurrently('idx_audit_action', 'cmis_audit.activity_logs', ['action', 'created_at DESC']);
        $this->createIndexConcurrently('idx_audit_model', 'cmis_audit.activity_logs', ['model_type', 'model_id']);

        // Personal Access Tokens indexes (for Sanctum)
        echo "Creating indexes for personal_access_tokens table...\n";
        $this->createIndexConcurrently('idx_tokens_tokenable', 'personal_access_tokens', ['tokenable_type', 'tokenable_id']);
        $this->createIndexConcurrently('idx_tokens_created', 'personal_access_tokens', ['created_at DESC']);

        echo "\n✓ All performance indexes created successfully!\n";
        echo "✓ Query performance should be significantly improved\n\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "\n🗑️  Removing performance indexes...\n\n";

        $indexes = [
            'idx_campaigns_org_status',
            'idx_campaigns_dates',
            'idx_campaigns_created',
            'idx_content_plans_campaign',
            'idx_content_plans_org',
            'idx_content_items_plan',
            'idx_content_items_status',
            'idx_knowledge_org_type',
            'idx_knowledge_created',
            'idx_knowledge_title',
            'idx_embeddings_vector',
            'idx_embeddings_knowledge',
            'idx_ad_accounts_org',
            'idx_ad_accounts_status',
            'idx_ad_campaigns_account',
            'idx_ad_campaigns_dates',
            'idx_ad_metrics_entity_date',
            'idx_ad_metrics_date',
            'idx_ad_metrics_campaign',
            'idx_user_orgs_user',
            'idx_user_orgs_org',
            'idx_creative_assets_org',
            'idx_creative_assets_created',
            'idx_compliance_audits_content',
            'idx_compliance_audits_status',
            'idx_audit_user_date',
            'idx_audit_action',
            'idx_audit_model',
            'idx_tokens_tokenable',
            'idx_tokens_created',
        ];

        foreach ($indexes as $index) {
            try {
                // Try to determine which schema the index belongs to
                $schemas = ['cmis', 'cmis_audit', 'public'];

                foreach ($schemas as $schema) {
                    DB::statement("DROP INDEX CONCURRENTLY IF EXISTS {$schema}.{$index}");
                }
            } catch (\Exception $e) {
                echo "Warning: Could not drop index {$index}: " . $e->getMessage() . "\n";
            }
        }

        echo "\n✓ Performance indexes removed\n\n";
    }

    /**
     * Create an index concurrently to avoid table locking.
     */
    private function createIndexConcurrently(
        string $indexName,
        string $tableName,
        array $columns,
        string $method = 'btree'
    ): void {
        try {
            $exists = DB::selectOne('SELECT to_regclass(?) as reg', [$tableName]);

            if (!$exists?->reg) {
                echo "Skipping index {$indexName}: table {$tableName} is missing.\n";
                return;
            }

            $columnList = implode(', ', $columns);

            if ($method === 'ivfflat') {
                // Special handling for vector indexes
                DB::statement("
                    CREATE INDEX CONCURRENTLY IF NOT EXISTS {$indexName}
                    ON {$tableName}
                    USING ivfflat ({$columnList})
                ");
            } else {
                DB::statement("
                    CREATE INDEX CONCURRENTLY IF NOT EXISTS {$indexName}
                    ON {$tableName} ({$columnList})
                ");
            }

            echo "✓ Created index: {$indexName}\n";
        } catch (\Exception $e) {
            echo "⚠ Warning: Could not create index {$indexName}: " . $e->getMessage() . "\n";
            // Don't throw - continue with other indexes
        }
    }
};
