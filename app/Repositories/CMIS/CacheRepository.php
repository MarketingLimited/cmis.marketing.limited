<?php

namespace App\Repositories\CMIS;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for CMIS Cache and Cleanup Functions
 * Encapsulates PostgreSQL functions related to cache management and cleanup operations
 */
class CacheRepository
{
    /**
     * Cleanup expired sessions and old data
     * Corresponds to: cmis.cleanup_expired_sessions()
     *
     * @return bool Success status
     */
    public function cleanupExpiredSessions(): bool
    {
        return DB::statement('SELECT cmis.cleanup_expired_sessions()');
    }

    /**
     * Cleanup old cache entries (unused for more than 30 days)
     * Corresponds to: cmis.cleanup_old_cache_entries()
     *
     * @return bool Success status
     */
    public function cleanupOldCacheEntries(): bool
    {
        return DB::statement('SELECT cmis.cleanup_old_cache_entries()');
    }

    /**
     * Refresh required fields cache
     * Corresponds to: cmis.refresh_required_fields_cache()
     *
     * @return bool Success status
     */
    public function refreshRequiredFieldsCache(): bool
    {
        return DB::statement('SELECT cmis.refresh_required_fields_cache()');
    }

    /**
     * Refresh required fields cache with metrics
     * Corresponds to: cmis.refresh_required_fields_cache_with_metrics()
     *
     * @return bool Success status
     */
    public function refreshRequiredFieldsCacheWithMetrics(): bool
    {
        return DB::statement('SELECT cmis.refresh_required_fields_cache_with_metrics()');
    }

    /**
     * Verify cache automation
     * Corresponds to: cmis.verify_cache_automation()
     *
     * @return Collection Collection of verification results
     */
    public function verifyCacheAutomation(): Collection
    {
        $results = DB::select('SELECT * FROM cmis.verify_cache_automation()');

        return collect($results);
    }

    /**
     * Refresh dashboard metrics
     * Corresponds to: cmis.refresh_dashboard_metrics()
     *
     * @return bool Success status
     */
    public function refreshDashboardMetrics(): bool
    {
        return DB::statement('SELECT cmis.refresh_dashboard_metrics()');
    }

    /**
     * Sync social metrics
     * Corresponds to: cmis.sync_social_metrics()
     *
     * @return bool Success status
     */
    public function syncSocialMetrics(): bool
    {
        return DB::statement('SELECT cmis.sync_social_metrics()');
    }
}
