<?php

namespace App\Repositories\Analytics;

use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for CMIS Analytics Functions
 * Encapsulates PostgreSQL functions related to analytics and reporting
 */
class AnalyticsRepository implements AnalyticsRepositoryInterface
{
    /**
     * Get migration reports
     * Corresponds to: cmis_analytics.report_migrations()
     *
     * @return Collection Collection of migration execution logs
     */
    public function reportMigrations(): Collection
    {
        $results = DB::select('SELECT * FROM cmis_analytics.report_migrations()');

        return collect($results);
    }

    /**
     * Run AI query on analytics data
     * Corresponds to: cmis_analytics.run_ai_query(p_org_id, p_prompt)
     *
     * @param string $orgId Organization UUID
     * @param string $prompt Query prompt text
     * @return bool Success status
     */
    public function runAiQuery(string $orgId, string $prompt): bool
    {
        return DB::statement(
            'SELECT cmis_analytics.run_ai_query(?, ?)',
            [$orgId, $prompt]
        );
    }

    /**
     * Snapshot performance metrics (last 30 days by default)
     * Corresponds to: cmis_analytics.snapshot_performance()
     *
     * @return Collection Collection of performance metrics with trends
     */
    public function snapshotPerformance(): Collection
    {
        $results = DB::select('SELECT * FROM cmis_analytics.snapshot_performance()');

        return collect($results);
    }

    /**
     * Snapshot performance metrics for specific number of days
     * Corresponds to: cmis_analytics.snapshot_performance(snapshot_days)
     *
     * @param int $snapshotDays Number of days to snapshot (default: 30)
     * @return Collection Collection of performance metrics with trends
     */
    public function snapshotPerformanceForDays(int $snapshotDays = 30): Collection
    {
        $results = DB::select(
            'SELECT * FROM cmis_analytics.snapshot_performance(?)',
            [$snapshotDays]
        );

        return collect($results);
    }
}
