<?php

namespace App\Repositories\Operations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for CMIS Operations Functions
 * Encapsulates PostgreSQL functions related to operational tasks
 */
class OperationsRepository
{
    /**
     * Cleanup stale assets
     * Corresponds to: cmis_ops.cleanup_stale_assets()
     *
     * @return bool Success status
     */
    public function cleanupStaleAssets(): bool
    {
        return DB::statement('SELECT cmis_ops.cleanup_stale_assets()');
    }

    /**
     * Generate AI summaries for campaigns
     * Corresponds to: cmis_ops.generate_ai_summary()
     *
     * @return Collection Collection of campaign summaries
     */
    public function generateAiSummary(): Collection
    {
        $results = DB::select('SELECT * FROM cmis_ops.generate_ai_summary()');

        return collect($results);
    }

    /**
     * Normalize metrics
     * Corresponds to: cmis_ops.normalize_metrics()
     *
     * @return bool Success status
     */
    public function normalizeMetrics(): bool
    {
        return DB::statement('SELECT cmis_ops.normalize_metrics()');
    }

    /**
     * Refresh AI insights
     * Corresponds to: cmis_ops.refresh_ai_insights()
     *
     * @return bool Success status
     */
    public function refreshAiInsights(): bool
    {
        return DB::statement('SELECT cmis_ops.refresh_ai_insights()');
    }

    /**
     * Sync integrations
     * Corresponds to: cmis_ops.sync_integrations()
     *
     * @return bool Success status
     */
    public function syncIntegrations(): bool
    {
        return DB::statement('SELECT cmis_ops.sync_integrations()');
    }

    /**
     * Update timestamp trigger function (normally called by trigger)
     * Corresponds to: cmis_ops.update_timestamp()
     *
     * Note: Automatically updates the updated_at timestamp
     * Can be called manually for timestamp maintenance
     *
     * @return bool Success status
     */
    public function updateTimestamp(): bool
    {
        return DB::statement('SELECT cmis_ops.update_timestamp()');
    }
}
