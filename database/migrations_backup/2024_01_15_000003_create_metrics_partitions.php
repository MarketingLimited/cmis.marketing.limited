<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates partitioned tables for ad_metrics and social_posts
     * to improve query performance on large datasets.
     */
    public function up(): void
    {
        // Check if partitioning is supported
        if (!$this->supportsPartitioning()) {
            $this->comment('PostgreSQL version does not support declarative partitioning. Skipping...');
            return;
        }

        // Partition ad_metrics by month
        $this->partitionAdMetrics();

        // Partition social_posts by month
        $this->partitionSocialPosts();
    }

    /**
     * Partition ad_metrics table by month
     */
    private function partitionAdMetrics(): void
    {
        DB::statement('
            -- Step 1: Rename existing table
            ALTER TABLE IF EXISTS cmis.ad_metrics RENAME TO ad_metrics_old;
        ');

        DB::statement('
            -- Step 2: Create new partitioned table
            CREATE TABLE IF NOT EXISTS cmis.ad_metrics (
                metric_id UUID DEFAULT gen_random_uuid(),
                campaign_id UUID NOT NULL,
                org_id UUID NOT NULL,
                date DATE NOT NULL,
                impressions BIGINT DEFAULT 0,
                clicks BIGINT DEFAULT 0,
                spend DECIMAL(15,2) DEFAULT 0,
                conversions INT DEFAULT 0,
                revenue DECIMAL(15,2) DEFAULT 0,
                ctr DECIMAL(5,2),
                cpc DECIMAL(10,2),
                cpm DECIMAL(10,2),
                roi DECIMAL(10,2),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP,
                PRIMARY KEY (metric_id, date)
            ) PARTITION BY RANGE (date);
        ');

        // Create partitions for last 3 months + next 3 months
        $this->createMonthlyPartitions('ad_metrics', 3, 3);

        DB::statement('
            -- Step 3: Copy data from old table (if exists)
            INSERT INTO cmis.ad_metrics
            SELECT * FROM cmis.ad_metrics_old
            WHERE date >= CURRENT_DATE - INTERVAL \'6 months\'
            ON CONFLICT DO NOTHING;
        ');

        // Create indexes on partitioned table
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ad_metrics_campaign_date
            ON cmis.ad_metrics (campaign_id, date DESC);
        ');

        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_ad_metrics_org_date
            ON cmis.ad_metrics (org_id, date DESC);
        ');

        $this->comment('✅ ad_metrics partitioned by month');
    }

    /**
     * Partition social_posts table by month
     */
    private function partitionSocialPosts(): void
    {
        DB::statement('
            -- Step 1: Rename existing table
            ALTER TABLE IF EXISTS cmis.social_posts RENAME TO social_posts_old;
        ');

        DB::statement('
            -- Step 2: Create new partitioned table
            CREATE TABLE IF NOT EXISTS cmis.social_posts (
                post_id UUID DEFAULT gen_random_uuid(),
                org_id UUID NOT NULL,
                campaign_id UUID,
                content TEXT,
                platforms JSONB,
                status VARCHAR(50),
                scheduled_for TIMESTAMP,
                published_at TIMESTAMP,
                engagement_metrics JSONB,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP,
                PRIMARY KEY (post_id, created_at)
            ) PARTITION BY RANGE (created_at);
        ');

        // Create partitions for last 6 months + next 6 months
        $this->createMonthlyPartitions('social_posts', 6, 6);

        DB::statement('
            -- Step 3: Copy data from old table (if exists)
            INSERT INTO cmis.social_posts
            SELECT * FROM cmis.social_posts_old
            WHERE created_at >= CURRENT_DATE - INTERVAL \'12 months\'
            ON CONFLICT DO NOTHING;
        ');

        // Create indexes
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_social_posts_org_created
            ON cmis.social_posts (org_id, created_at DESC);
        ');

        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_social_posts_scheduled
            ON cmis.social_posts (scheduled_for)
            WHERE status = \'scheduled\';
        ');

        $this->comment('✅ social_posts partitioned by month');
    }

    /**
     * Create monthly partitions
     *
     * @param string $table Table name
     * @param int $pastMonths Number of past months
     * @param int $futureMonths Number of future months
     */
    private function createMonthlyPartitions(string $table, int $pastMonths, int $futureMonths): void
    {
        $startMonth = now()->subMonths($pastMonths)->startOfMonth();
        $endMonth = now()->addMonths($futureMonths)->endOfMonth();

        $current = clone $startMonth;

        while ($current <= $endMonth) {
            $partitionName = "{$table}_" . $current->format('Y_m');
            $rangeStart = $current->format('Y-m-01');
            $rangeEnd = $current->copy()->addMonth()->format('Y-m-01');

            DB::statement("
                CREATE TABLE IF NOT EXISTS cmis.{$partitionName}
                PARTITION OF cmis.{$table}
                FOR VALUES FROM ('{$rangeStart}') TO ('{$rangeEnd}');
            ");

            $current->addMonth();
        }

        $this->comment("✅ Created partitions for {$table}: " . ($pastMonths + $futureMonths + 1) . " months");
    }

    /**
     * Check if PostgreSQL supports partitioning
     */
    private function supportsPartitioning(): bool
    {
        $version = DB::selectOne('SELECT version()');
        preg_match('/PostgreSQL (\d+)\.(\d+)/', $version->version ?? '', $matches);

        $major = (int)($matches[1] ?? 0);

        // Partitioning is available from PostgreSQL 10+
        return $major >= 10;
    }

    /**
     * Output comment message
     */
    private function comment(string $message): void
    {
        echo "\n{$message}\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!$this->supportsPartitioning()) {
            return;
        }

        // Restore from old tables
        DB::statement('DROP TABLE IF EXISTS cmis.ad_metrics CASCADE;');
        DB::statement('ALTER TABLE IF EXISTS cmis.ad_metrics_old RENAME TO ad_metrics;');

        DB::statement('DROP TABLE IF EXISTS cmis.social_posts CASCADE;');
        DB::statement('ALTER TABLE IF EXISTS cmis.social_posts_old RENAME TO social_posts;');
    }
};
