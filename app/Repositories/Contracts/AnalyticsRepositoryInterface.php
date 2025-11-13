<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface AnalyticsRepositoryInterface
{
    /**
     * Report migrations
     */
    public function reportMigrations(): Collection;

    /**
     * Run AI query on analytics data
     */
    public function runAiQuery(string $orgId, string $prompt): bool;

    /**
     * Snapshot performance (last 30 days by default)
     */
    public function snapshotPerformance(): Collection;

    /**
     * Snapshot performance for specific days
     */
    public function snapshotPerformanceForDays(int $snapshotDays = 30): Collection;
}
