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

    /**
     * Get publishing queue configuration
     */
    public function getPublishingQueue(string $socialAccountId): ?object;

    /**
     * Create or update publishing queue
     */
    public function upsertPublishingQueue(
        string $orgId,
        string $socialAccountId,
        array $config
    ): object;

    /**
     * Get next available time slot
     */
    public function getNextAvailableSlot(
        string $socialAccountId,
        ?\DateTime $afterTime = null
    ): ?\DateTime;

    /**
     * Get queued posts for account
     */
    public function getQueuedPosts(string $socialAccountId): Collection;

    /**
     * Schedule post to queue
     */
    public function schedulePostToQueue(
        string $postId,
        string $socialAccountId,
        \DateTime $scheduledFor
    ): bool;

    /**
     * Remove post from queue
     */
    public function removeFromQueue(string $postId): bool;
}
