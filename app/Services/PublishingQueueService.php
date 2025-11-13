<?php

namespace App\Services;

use App\Repositories\Contracts\SocialMediaRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing publishing queues (Buffer-style scheduling)
 * Implements Sprint 2.1: Queue per-Channel
 *
 * Features:
 * - Create custom posting schedules per social account
 * - Define time slots for each weekday
 * - Automatic slot assignment for new posts
 * - Queue visualization and management
 */
class PublishingQueueService
{
    protected SocialMediaRepositoryInterface $socialMediaRepo;

    public function __construct(SocialMediaRepositoryInterface $socialMediaRepo)
    {
        $this->socialMediaRepo = $socialMediaRepo;
    }

    /**
     * Get queue configuration for social account
     *
     * @param string $socialAccountId
     * @return object|null
     */
    public function getQueue(string $socialAccountId): ?object
    {
        try {
            $cacheKey = "publishing_queue:{$socialAccountId}";

            return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($socialAccountId) {
                return $this->socialMediaRepo->getPublishingQueue($socialAccountId);
            });
        } catch (\Exception $e) {
            Log::error('Failed to get publishing queue', [
                'social_account_id' => $socialAccountId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create or update queue configuration
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param array $config Must contain: weekdays_enabled, time_slots, timezone
     * @return object
     * @throws \InvalidArgumentException
     */
    public function upsertQueue(string $orgId, string $socialAccountId, array $config): object
    {
        // Validate config
        $this->validateQueueConfig($config);

        try {
            $queue = $this->socialMediaRepo->upsertPublishingQueue(
                $orgId,
                $socialAccountId,
                $config
            );

            // Clear cache
            Cache::forget("publishing_queue:{$socialAccountId}");

            Log::info('Publishing queue updated', [
                'social_account_id' => $socialAccountId,
                'org_id' => $orgId
            ]);

            return $queue;
        } catch (\Exception $e) {
            Log::error('Failed to upsert publishing queue', [
                'social_account_id' => $socialAccountId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get next available time slot for account
     *
     * @param string $socialAccountId
     * @param \DateTime|null $afterTime Start searching after this time
     * @return \DateTime|null
     */
    public function getNextAvailableSlot(string $socialAccountId, ?\DateTime $afterTime = null): ?\DateTime
    {
        try {
            $afterTime = $afterTime ?? new \DateTime();

            return $this->socialMediaRepo->getNextAvailableSlot(
                $socialAccountId,
                $afterTime
            );
        } catch (\Exception $e) {
            Log::error('Failed to get next available slot', [
                'social_account_id' => $socialAccountId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Schedule post to queue
     * If no time specified, automatically assigns to next available slot
     *
     * @param string $postId
     * @param string $socialAccountId
     * @param \DateTime|null $scheduledFor
     * @return bool
     */
    public function schedulePost(string $postId, string $socialAccountId, ?\DateTime $scheduledFor = null): bool
    {
        try {
            // If no time specified, get next available slot
            if ($scheduledFor === null) {
                $scheduledFor = $this->getNextAvailableSlot($socialAccountId);

                if ($scheduledFor === null) {
                    Log::warning('No available slots for post', [
                        'post_id' => $postId,
                        'social_account_id' => $socialAccountId
                    ]);
                    return false;
                }
            }

            $success = $this->socialMediaRepo->schedulePostToQueue(
                $postId,
                $socialAccountId,
                $scheduledFor
            );

            if ($success) {
                Log::info('Post scheduled to queue', [
                    'post_id' => $postId,
                    'social_account_id' => $socialAccountId,
                    'scheduled_for' => $scheduledFor->format('Y-m-d H:i:s')
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Failed to schedule post', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove post from queue
     *
     * @param string $postId
     * @return bool
     */
    public function removeFromQueue(string $postId): bool
    {
        try {
            $success = $this->socialMediaRepo->removeFromQueue($postId);

            if ($success) {
                Log::info('Post removed from queue', ['post_id' => $postId]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Failed to remove post from queue', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get all queued posts for account
     *
     * @param string $socialAccountId
     * @return Collection
     */
    public function getQueuedPosts(string $socialAccountId): Collection
    {
        try {
            return $this->socialMediaRepo->getQueuedPosts($socialAccountId);
        } catch (\Exception $e) {
            Log::error('Failed to get queued posts', [
                'social_account_id' => $socialAccountId,
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Validate queue configuration
     *
     * @param array $config
     * @throws \InvalidArgumentException
     */
    protected function validateQueueConfig(array $config): void
    {
        // Validate weekdays_enabled format (7 characters, 0 or 1)
        if (isset($config['weekdays_enabled'])) {
            if (!preg_match('/^[01]{7}$/', $config['weekdays_enabled'])) {
                throw new \InvalidArgumentException(
                    'weekdays_enabled must be 7 characters of 0 or 1 (MTWTFSS)'
                );
            }
        }

        // Validate time_slots is array
        if (isset($config['time_slots']) && !is_array($config['time_slots'])) {
            throw new \InvalidArgumentException('time_slots must be an array');
        }

        // Validate timezone
        if (isset($config['timezone'])) {
            try {
                new \DateTimeZone($config['timezone']);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid timezone: ' . $config['timezone']);
            }
        }
    }

    /**
     * Get queue statistics
     *
     * @param string $socialAccountId
     * @return array
     */
    public function getQueueStatistics(string $socialAccountId): array
    {
        $queue = $this->getQueue($socialAccountId);
        $queuedPosts = $this->getQueuedPosts($socialAccountId);

        if (!$queue) {
            return [
                'configured' => false,
                'queued_posts_count' => 0,
                'next_slot' => null
            ];
        }

        $nextSlot = $this->getNextAvailableSlot($socialAccountId);

        return [
            'configured' => true,
            'queued_posts_count' => $queuedPosts->count(),
            'next_slot' => $nextSlot?->format('Y-m-d H:i:s'),
            'timezone' => $queue->timezone ?? 'UTC',
            'active_days' => substr_count($queue->weekdays_enabled ?? '1111100', '1'),
            'slots_per_day' => is_string($queue->time_slots)
                ? count(json_decode($queue->time_slots, true))
                : count($queue->time_slots ?? [])
        ];
    }
}
