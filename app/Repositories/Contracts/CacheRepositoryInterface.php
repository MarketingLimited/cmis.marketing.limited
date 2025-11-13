<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface CacheRepositoryInterface
{
    /**
     * Cleanup expired sessions and old data
     */
    public function cleanupExpiredSessions(): bool;

    /**
     * Cleanup old cache entries (unused for more than 30 days)
     */
    public function cleanupOldCacheEntries(): bool;

    /**
     * Refresh required fields cache
     */
    public function refreshRequiredFieldsCache(): bool;

    /**
     * Refresh required fields cache with metrics
     */
    public function refreshRequiredFieldsCacheWithMetrics(): bool;

    /**
     * Verify cache automation
     */
    public function verifyCacheAutomation(): Collection;

    /**
     * Refresh dashboard metrics
     */
    public function refreshDashboardMetrics(): bool;

    /**
     * Sync social metrics
     */
    public function syncSocialMetrics(): bool;
}
