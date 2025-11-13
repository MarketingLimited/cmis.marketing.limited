<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface SocialMediaRepositoryInterface
{
    /**
     * Get social account metrics
     */
    public function getAccountMetrics(
        string $accountId,
        ?string $startDate = null,
        ?string $endDate = null
    ): ?object;

    /**
     * Get post performance
     */
    public function getPostPerformance(
        string $postId
    ): ?object;

    /**
     * Analyze best posting times
     */
    public function analyzeBestPostingTimes(
        string $accountId,
        int $lookbackDays = 30
    ): Collection;

    /**
     * Get engagement trends
     */
    public function getEngagementTrends(
        string $accountId,
        string $period = 'weekly'
    ): Collection;
}
