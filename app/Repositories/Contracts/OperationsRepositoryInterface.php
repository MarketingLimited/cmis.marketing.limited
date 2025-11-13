<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface OperationsRepositoryInterface
{
    /**
     * Cleanup stale creative assets
     */
    public function cleanupStaleAssets(int $daysOld = 90): bool;

    /**
     * Normalize metrics across platforms
     */
    public function normalizeMetrics(string $orgId): bool;

    /**
     * Refresh materialized views
     */
    public function refreshMaterializedViews(): bool;

    /**
     * Get system health status
     */
    public function getSystemHealth(): Collection;
}
