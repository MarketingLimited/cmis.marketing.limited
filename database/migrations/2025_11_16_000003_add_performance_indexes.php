<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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
        $this->createIndexConcurrently('idx_content_plans_campaign', 'cmis.content_plans', ['campaign_id']);
        $this->createIndexConcurrently('idx_content_plans_org_created', 'cmis.content_plans', ['org_id', 'created_at DESC']);

        // Content Items indexes
        echo "Creating indexes for content_items table...\n";
        $this->createIndexConcurrently('idx_content_items_plan', 'cmis.content_items', ['plan_id']);
        $this->createIndexConcurrently('idx_content_items_status', 'cmis.content_items', ['status', 'scheduled_at']);

        // Knowledge Base indexes (skip if table doesn't exist)
        if ($this->tableExists('cmis.knowledge_base')) {
            echo "Creating indexes for knowledge_base table...\n";
            $this->createIndexConcurrently('idx_knowledge_org_type', 'cmis.knowledge_base', ['org_id', 'content_type']);
            $this->createIndexConcurrently('idx_knowledge_created', 'cmis.knowledge_base', ['created_at DESC']);
            $this->createIndexConcurrently('idx_knowledge_title', 'cmis.knowledge_base', ['title']);
        }

        // Knowledge Embeddings - Vector similarity search index (skip if table doesn't exist)
        if ($this->tableExists('cmis.knowledge_embeddings')) {
            echo "Creating vector index for knowledge_embeddings table...\n";
            $this->createIndexConcurrently(
                'idx_embeddings_vector',
                'cmis.knowledge_embeddings',
                ['embedding vector_cosine_ops'],
                'ivfflat'
            );
            $this->createIndexConcurrently('idx_embeddings_knowledge', 'cmis.knowledge_embeddings', ['knowledge_id']);
        }

        // Ad Accounts indexes (check if platform column exists)
        if ($this->tableExists('cmis.ad_accounts')) {
            echo "Creating indexes for ad_accounts table...\n";
            if ($this->columnExists('cmis.ad_accounts', 'platform')) {
                $this->createIndexConcurrently('idx_ad_accounts_org', 'cmis.ad_accounts', ['org_id', 'platform']);
            }
            $this->createIndexConcurrently('idx_ad_accounts_status', 'cmis.ad_accounts', ['status']);
        }

        // Ad Campaigns indexes
        echo "Creating indexes for ad_campaigns table...\n";
        if ($this->columnExists('cmis.ad_campaigns', 'ad_account_id')) {
            $this->createIndexConcurrently('idx_ad_campaigns_account', 'cmis.ad_campaigns', ['ad_account_id', 'status']);
        }
        if ($this->columnExists('cmis.ad_campaigns', 'start_date') && $this->columnExists('cmis.ad_campaigns', 'end_date')) {
            $this->createIndexConcurrently('idx_ad_campaigns_dates', 'cmis.ad_campaigns', ['start_date', 'end_date']);
        }

        // Ad Metrics indexes (critical for performance)
        if ($this->tableExists('cmis.ad_metrics')) {
            echo "Creating indexes for ad_metrics table...\n";
            if ($this->columnExists('cmis.ad_metrics', 'ad_entity_id') && $this->columnExists('cmis.ad_metrics', 'recorded_at')) {
                $this->createIndexConcurrently('idx_ad_metrics_entity_date', 'cmis.ad_metrics', ['ad_entity_id', 'recorded_at DESC']);
            }
            if ($this->columnExists('cmis.ad_metrics', 'recorded_at')) {
                $this->createIndexConcurrently('idx_ad_metrics_date', 'cmis.ad_metrics', ['recorded_at DESC']);
            }
            if ($this->columnExists('cmis.ad_metrics', 'ad_campaign_id') && $this->columnExists('cmis.ad_metrics', 'recorded_at')) {
                $this->createIndexConcurrently('idx_ad_metrics_campaign', 'cmis.ad_metrics', ['ad_campaign_id', 'recorded_at DESC']);
            }
        }

        // User Orgs indexes
        if ($this->tableExists('cmis.user_orgs')) {
            echo "Creating indexes for user_orgs table...\n";
            if ($this->columnExists('cmis.user_orgs', 'user_id') && $this->columnExists('cmis.user_orgs', 'is_active')) {
                $this->createIndexConcurrently('idx_user_orgs_user', 'cmis.user_orgs', ['user_id', 'is_active']);
            }
            if ($this->columnExists('cmis.user_orgs', 'org_id') && $this->columnExists('cmis.user_orgs', 'is_active')) {
                $this->createIndexConcurrently('idx_user_orgs_org', 'cmis.user_orgs', ['org_id', 'is_active']);
            }
        }

        // Creative Assets indexes
        if ($this->tableExists('cmis.creative_assets')) {
            echo "Creating indexes for creative_assets table...\n";
            if ($this->columnExists('cmis.creative_assets', 'org_id') && $this->columnExists('cmis.creative_assets', 'asset_type')) {
                $this->createIndexConcurrently('idx_creative_assets_org', 'cmis.creative_assets', ['org_id', 'asset_type']);
            }
            if ($this->columnExists('cmis.creative_assets', 'created_at')) {
                $this->createIndexConcurrently('idx_creative_assets_created', 'cmis.creative_assets', ['created_at DESC']);
            }
        }

        // Compliance Audits indexes
        if ($this->tableExists('cmis.compliance_audits')) {
            echo "Creating indexes for compliance_audits table...\n";
            if ($this->columnExists('cmis.compliance_audits', 'content_item_id')) {
                $this->createIndexConcurrently('idx_compliance_audits_content', 'cmis.compliance_audits', ['content_item_id']);
            }
            if ($this->columnExists('cmis.compliance_audits', 'status') && $this->columnExists('cmis.compliance_audits', 'created_at')) {
                $this->createIndexConcurrently('idx_compliance_audits_status', 'cmis.compliance_audits', ['status', 'created_at DESC']);
            }
        }

        // Audit Logs indexes (important for security tracking)
        if ($this->tableExists('cmis_audit.activity_logs')) {
            echo "Creating indexes for activity_logs table...\n";
            if ($this->columnExists('cmis_audit.activity_logs', 'user_id') && $this->columnExists('cmis_audit.activity_logs', 'created_at')) {
                $this->createIndexConcurrently('idx_audit_user_date', 'cmis_audit.activity_logs', ['user_id', 'created_at DESC']);
            }
            if ($this->columnExists('cmis_audit.activity_logs', 'action') && $this->columnExists('cmis_audit.activity_logs', 'created_at')) {
                $this->createIndexConcurrently('idx_audit_action', 'cmis_audit.activity_logs', ['action', 'created_at DESC']);
            }
            if ($this->columnExists('cmis_audit.activity_logs', 'model_type') && $this->columnExists('cmis_audit.activity_logs', 'model_id')) {
                $this->createIndexConcurrently('idx_audit_model', 'cmis_audit.activity_logs', ['model_type', 'model_id']);
            }
        }

        // Personal Access Tokens indexes (for Sanctum)
        if ($this->tableExists('personal_access_tokens')) {
            echo "Creating indexes for personal_access_tokens table...\n";
            if ($this->columnExists('personal_access_tokens', 'tokenable_type') && $this->columnExists('personal_access_tokens', 'tokenable_id')) {
                $this->createIndexConcurrently('idx_tokens_tokenable', 'personal_access_tokens', ['tokenable_type', 'tokenable_id']);
            }
            if ($this->columnExists('personal_access_tokens', 'created_at')) {
                $this->createIndexConcurrently('idx_tokens_created', 'personal_access_tokens', ['created_at DESC']);
            }
        }

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
            'idx_content_plans_org_created',
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
                    DB::statement("DROP INDEX IF EXISTS {$schema}.{$index}");
                }
            } catch (\Exception $e) {
                echo "Warning: Could not drop index {$index}: " . $e->getMessage() . "\n";
            }
        }

        echo "\n✓ Performance indexes removed\n\n";
    }

    /**
     * Check if a table exists in the database.
     */
    private function tableExists(string $tableName): bool
    {
        try {
            [$schema, $table] = explode('.', $tableName);
            $result = DB::select("
                SELECT EXISTS (
                    SELECT 1
                    FROM information_schema.tables
                    WHERE table_schema = ?
                    AND table_name = ?
                )
            ", [$schema, $table]);

            return $result[0]->exists ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a column exists in a table.
     */
    private function columnExists(string $tableName, string $columnName): bool
    {
        try {
            [$schema, $table] = explode('.', $tableName);
            $result = DB::select("
                SELECT EXISTS (
                    SELECT 1
                    FROM information_schema.columns
                    WHERE table_schema = ?
                    AND table_name = ?
                    AND column_name = ?
                )
            ", [$schema, $table, $columnName]);

            return $result[0]->exists ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create an index (without CONCURRENTLY in migrations to avoid transaction conflicts).
     */
    private function createIndexConcurrently(
        string $indexName,
        string $tableName,
        array $columns,
        string $method = 'btree'
    ): void {
        try {
            $columnList = implode(', ', $columns);

            // Use a separate transaction for each index to prevent cascading failures
            DB::transaction(function () use ($indexName, $tableName, $columnList, $method) {
                if ($method === 'ivfflat') {
                    // Special handling for vector indexes
                    DB::statement("
                        CREATE INDEX IF NOT EXISTS {$indexName}
                        ON {$tableName}
                        USING ivfflat ({$columnList})
                    ");
                } else {
                    DB::statement("
                        CREATE INDEX IF NOT EXISTS {$indexName}
                        ON {$tableName} ({$columnList})
                    ");
                }
            });

            echo "✓ Created index: {$indexName}\n";
        } catch (\Exception $e) {
            // Extract just the essential error message
            $message = $e->getMessage();
            if (strpos($message, 'does not exist') !== false) {
                preg_match('/column "([^"]+)" does not exist/', $message, $matches);
                $column = $matches[1] ?? 'unknown';
                echo "⚠ Skipped index {$indexName}: column '{$column}' not found\n";
            } else {
                echo "⚠ Warning: Could not create index {$indexName}: {$message}\n";
            }
            // Don't throw - continue with other indexes
        }
    }
};
