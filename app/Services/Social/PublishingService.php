<?php

namespace App\Services\Social;

use App\Models\Social\ScheduledPost;
use App\Models\Social\PlatformPost;
use App\Models\Social\PublishingQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublishingService
{
    /**
     * Publish post to all scheduled platforms.
     */
    public function publishPost(ScheduledPost $post): array
    {
        $post->markAsPublishing();
        $results = [];

        foreach ($post->platforms as $platform) {
            $results[$platform] = $this->publishToPlatform($post, $platform);
        }

        // Check if all platforms succeeded
        $allSucceeded = !in_array(false, $results, true);

        if ($allSucceeded) {
            $post->markAsPublished();
        } else {
            $failedPlatforms = array_keys(array_filter($results, fn($r) => $r === false));
            $post->markAsFailed('Failed to publish to: ' . implode(', ', $failedPlatforms));
        }

        return $results;
    }

    /**
     * Publish to a specific platform.
     */
    public function publishToPlatform(ScheduledPost $post, string $platform): bool
    {
        DB::beginTransaction();
        try {
            // Create platform post record
            $platformPost = PlatformPost::firstOrCreate(
                [
                    'org_id' => $post->org_id,
                    'scheduled_post_id' => $post->post_id,
                    'platform' => $platform,
                ],
                [
                    'status' => 'pending',
                ]
            );

            $platformPost->markAsPublishing();

            // Get platform-specific content
            $content = $post->getContentForPlatform($platform);

            // Publish via platform API
            $response = $this->callPlatformAPI($platform, $post, $content);

            // Mark as published
            $platformPost->markAsPublished(
                $response['post_id'],
                $response['url']
            );

            $platformPost->update(['platform_response' => $response]);

            // Update queue item
            PublishingQueue::where('scheduled_post_id', $post->post_id)
                ->where('platform', $platform)
                ->first()
                ?->markAsCompleted();

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to publish to platform', [
                'post_id' => $post->post_id,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            $platformPost?->markAsFailed($e->getMessage());

            // Update queue item
            $queueItem = PublishingQueue::where('scheduled_post_id', $post->post_id)
                ->where('platform', $platform)
                ->first();

            if ($queueItem) {
                $queueItem->markAsFailed($e->getMessage());
            }

            return false;
        }
    }

    /**
     * Call platform API to publish content.
     */
    protected function callPlatformAPI(string $platform, ScheduledPost $post, string $content): array
    {
        // TODO: Implement actual platform API calls
        // For now, return mock response

        $postId = $platform . '_' . uniqid();
        $url = "https://{$platform}.com/posts/{$postId}";

        Log::info('Publishing to platform (mock)', [
            'platform' => $platform,
            'post_id' => $post->post_id,
            'content_length' => strlen($content),
        ]);

        // Simulate API delay
        usleep(500000); // 0.5 seconds

        return [
            'post_id' => $postId,
            'url' => $url,
            'status' => 'published',
            'created_time' => now()->toISOString(),
        ];
    }

    /**
     * Sync platform post metrics.
     */
    public function syncPlatformMetrics(PlatformPost $platformPost): void
    {
        try {
            // Fetch metrics from platform API
            $metrics = $this->fetchPlatformMetrics($platformPost->platform, $platformPost->platform_post_id_external);

            // Update metrics
            $platformPost->updateMetrics($metrics);

            Log::info('Synced platform metrics', [
                'platform' => $platformPost->platform,
                'platform_post_id' => $platformPost->platform_post_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync platform metrics', [
                'platform_post_id' => $platformPost->platform_post_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fetch metrics from platform API.
     */
    protected function fetchPlatformMetrics(string $platform, string $externalId): array
    {
        // TODO: Implement actual platform API calls
        // For now, return mock metrics

        return [
            'likes' => rand(50, 500),
            'comments' => rand(5, 50),
            'shares' => rand(2, 30),
            'views' => rand(1000, 10000),
        ];
    }

    /**
     * Process publishing queue.
     */
    public function processQueue(): array
    {
        $queueItems = PublishingQueue::due()->get();
        $results = ['success' => 0, 'failed' => 0];

        foreach ($queueItems as $queueItem) {
            try {
                $post = $queueItem->scheduledPost;

                if (!$post->canBePublished()) {
                    continue;
                }

                $queueItem->markAsProcessing();

                $success = $this->publishToPlatform($post, $queueItem->platform);

                if ($success) {
                    $results['success']++;
                } else {
                    $results['failed']++;

                    // Retry if attempts remaining
                    if ($queueItem->canRetry()) {
                        $queueItem->update([
                            'status' => 'pending',
                            'scheduled_for' => now()->addMinutes(5), // Retry in 5 minutes
                        ]);
                    }
                }

            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('Queue processing failed', [
                    'queue_id' => $queueItem->queue_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Bulk sync metrics for all published posts.
     */
    public function bulkSyncMetrics(string $orgId): array
    {
        $platformPosts = PlatformPost::where('org_id', $orgId)
            ->where('status', 'published')
            ->where(function($q) {
                $q->whereNull('last_synced_at')
                  ->orWhere('last_synced_at', '<', now()->subHours(1));
            })
            ->get();

        $results = ['synced' => 0, 'failed' => 0];

        foreach ($platformPosts as $platformPost) {
            try {
                $this->syncPlatformMetrics($platformPost);
                $results['synced']++;
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        return $results;
    }
}
