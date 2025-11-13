<?php

namespace App\Repositories\CMIS;

use App\Repositories\Contracts\SocialMediaRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for CMIS Social Media Functions
 * Handles social media accounts, posts, and publishing queues
 */
class SocialMediaRepository implements SocialMediaRepositoryInterface
{
    /**
     * Get social account metrics
     *
     * @param string $accountId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return object|null
     */
    public function getAccountMetrics(
        string $accountId,
        ?string $startDate = null,
        ?string $endDate = null
    ): ?object {
        $result = DB::select(
            'SELECT * FROM cmis.get_account_metrics(?, ?, ?)',
            [$accountId, $startDate, $endDate]
        );

        return $result[0] ?? null;
    }

    /**
     * Get post performance
     *
     * @param string $postId
     * @return object|null
     */
    public function getPostPerformance(string $postId): ?object
    {
        $result = DB::select(
            'SELECT * FROM cmis.get_post_performance(?)',
            [$postId]
        );

        return $result[0] ?? null;
    }

    /**
     * Analyze best posting times
     *
     * @param string $accountId
     * @param int $lookbackDays
     * @return Collection
     */
    public function analyzeBestPostingTimes(
        string $accountId,
        int $lookbackDays = 30
    ): Collection {
        $results = DB::select(
            'SELECT * FROM cmis.analyze_best_posting_times(?, ?)',
            [$accountId, $lookbackDays]
        );

        return collect($results);
    }

    /**
     * Get engagement trends
     *
     * @param string $accountId
     * @param string $period
     * @return Collection
     */
    public function getEngagementTrends(
        string $accountId,
        string $period = 'weekly'
    ): Collection {
        $results = DB::select(
            'SELECT * FROM cmis.get_engagement_trends(?, ?)',
            [$accountId, $period]
        );

        return collect($results);
    }

    /**
     * Get publishing queue configuration
     *
     * @param string $socialAccountId
     * @return object|null
     */
    public function getPublishingQueue(string $socialAccountId): ?object
    {
        $result = DB::select(
            'SELECT * FROM cmis.get_publishing_queue(?)',
            [$socialAccountId]
        );

        return $result[0] ?? null;
    }

    /**
     * Create or update publishing queue
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param array $config
     * @return object
     */
    public function upsertPublishingQueue(
        string $orgId,
        string $socialAccountId,
        array $config
    ): object {
        $result = DB::select(
            'SELECT * FROM cmis.upsert_publishing_queue(?, ?, ?, ?, ?)',
            [
                $orgId,
                $socialAccountId,
                $config['weekdays_enabled'] ?? '1111100', // Mon-Fri default
                json_encode($config['time_slots'] ?? []),
                $config['timezone'] ?? 'UTC'
            ]
        );

        return $result[0];
    }

    /**
     * Get next available time slot
     *
     * @param string $socialAccountId
     * @param \DateTime|null $afterTime
     * @return \DateTime|null
     */
    public function getNextAvailableSlot(
        string $socialAccountId,
        ?\DateTime $afterTime = null
    ): ?\DateTime {
        $after = $afterTime ? $afterTime->format('Y-m-d H:i:s') : null;

        $result = DB::select(
            'SELECT * FROM cmis.get_next_available_slot(?, ?)',
            [$socialAccountId, $after]
        );

        if (empty($result) || !isset($result[0]->next_slot)) {
            return null;
        }

        return new \DateTime($result[0]->next_slot);
    }

    /**
     * Get queued posts for account
     *
     * @param string $socialAccountId
     * @return Collection
     */
    public function getQueuedPosts(string $socialAccountId): Collection
    {
        $results = DB::select(
            'SELECT * FROM cmis.get_queued_posts(?)',
            [$socialAccountId]
        );

        return collect($results);
    }

    /**
     * Schedule post to queue
     *
     * @param string $postId
     * @param string $socialAccountId
     * @param \DateTime $scheduledFor
     * @return bool
     */
    public function schedulePostToQueue(
        string $postId,
        string $socialAccountId,
        \DateTime $scheduledFor
    ): bool {
        $result = DB::select(
            'SELECT cmis.schedule_post_to_queue(?, ?, ?) as success',
            [
                $postId,
                $socialAccountId,
                $scheduledFor->format('Y-m-d H:i:s')
            ]
        );

        return $result[0]->success ?? false;
    }

    /**
     * Remove post from queue
     *
     * @param string $postId
     * @return bool
     */
    public function removeFromQueue(string $postId): bool
    {
        $result = DB::select(
            'SELECT cmis.remove_post_from_queue(?) as success',
            [$postId]
        );

        return $result[0]->success ?? false;
    }
}
