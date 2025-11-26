<?php

namespace App\Services\Social;

use App\Models\Social\ScheduledPost;
use App\Models\Social\PlatformPost;
use App\Models\Social\PublishingQueue;
use App\Models\Platform\PlatformConnection;
use App\Services\Social\PlatformServiceFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
        // Get active platform connection for this organization
        $connection = PlatformConnection::where('org_id', $post->org_id)
            ->where('platform', $platform)
            ->where('status', 'active')
            ->first();

        if (!$connection) {
            throw new \Exception("No active connection found for platform: {$platform}");
        }

        // Ensure token is fresh (auto-refresh if needed)
        $connection = PlatformServiceFactory::ensureFreshToken($connection);

        // Create platform service instance
        $service = PlatformServiceFactory::createFromConnection($connection);

        // Prepare content in platform-specific format
        $platformContent = $this->prepareContentForPlatform($post, $platform, $content);

        Log::info('Publishing to platform via API', [
            'platform' => $platform,
            'post_id' => $post->post_id,
            'post_type' => $platformContent['post_type'] ?? 'feed',
            'has_media' => !empty($platformContent['media_files'] ?? []),
        ]);

        // Publish via platform service
        $result = $service->publish($platformContent);

        return [
            'post_id' => $result['external_id'],
            'url' => $result['url'],
            'status' => 'published',
            'created_time' => now()->toISOString(),
            'platform_data' => $result['platform_data'] ?? [],
        ];
    }

    /**
     * Prepare content in platform-specific format.
     */
    protected function prepareContentForPlatform(ScheduledPost $post, string $platform, string $content): array
    {
        $platformContent = [
            'text' => $content,
            'post_type' => $post->post_type ?? 'feed',
        ];

        // Add media files if present
        if ($post->media && is_array($post->media)) {
            $mediaFiles = [];

            foreach ($post->media as $mediaItem) {
                // Convert storage URL to local file path
                if (isset($mediaItem['url'])) {
                    $relativePath = str_replace(url(Storage::url('')), '', $mediaItem['url']);
                    $localPath = Storage::disk('public')->path($relativePath);

                    if (file_exists($localPath)) {
                        $mediaFiles[] = $localPath;
                    }
                }
            }

            if (!empty($mediaFiles)) {
                $platformContent['media_files'] = $mediaFiles;
            }
        }

        // Platform-specific content transformations
        switch ($platform) {
            case 'youtube':
                // YouTube requires title and description
                $platformContent['title'] = $post->title ?? mb_substr($content, 0, 100);
                $platformContent['description'] = $content;
                $platformContent['privacy_status'] = $post->platform_settings['youtube']['privacy'] ?? 'public';
                break;

            case 'linkedin':
                // LinkedIn requires author URN
                $platformContent['author'] = $post->platform_settings['linkedin']['author'] ??
                    'urn:li:person:' . ($post->platform_settings['linkedin']['person_id'] ?? '');
                $platformContent['visibility'] = $post->platform_settings['linkedin']['visibility'] ?? 'PUBLIC';
                break;

            case 'twitter':
            case 'x':
                // Twitter has thread support
                if (isset($post->platform_settings['twitter']['is_thread']) && $post->platform_settings['twitter']['is_thread']) {
                    $platformContent['post_type'] = 'thread';
                    $platformContent['tweets'] = $post->platform_settings['twitter']['tweets'] ?? [
                        ['text' => $content]
                    ];
                }
                break;

            case 'tiktok':
                // TikTok requires video
                if (empty($platformContent['media_files'])) {
                    throw new \Exception('TikTok posts require video content');
                }
                break;

            case 'pinterest':
                // Pinterest requires image and board
                if (empty($platformContent['media_files'])) {
                    throw new \Exception('Pinterest posts require image content');
                }
                $platformContent['board_id'] = $post->platform_settings['pinterest']['board_id'] ?? null;
                if (!$platformContent['board_id']) {
                    throw new \Exception('Pinterest posts require board_id');
                }
                break;
        }

        return $platformContent;
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
        try {
            // Get active platform connection
            $connection = PlatformConnection::where('platform', $platform)
                ->where('status', 'active')
                ->first();

            if (!$connection) {
                Log::warning('No active connection for metrics sync', ['platform' => $platform]);
                return [];
            }

            // Ensure token is fresh
            $connection = PlatformServiceFactory::ensureFreshToken($connection);

            // Create platform service
            $service = PlatformServiceFactory::createFromConnection($connection);

            // Check if service supports analytics
            if (!method_exists($service, 'getAnalytics')) {
                Log::info('Platform does not support analytics API', ['platform' => $platform]);
                return [];
            }

            // Fetch analytics from platform
            $analytics = $service->getAnalytics($externalId);

            // Normalize metrics across platforms
            return $this->normalizeMetrics($platform, $analytics);

        } catch (\Exception $e) {
            Log::error('Failed to fetch platform metrics', [
                'platform' => $platform,
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Normalize metrics from different platforms to common format.
     */
    protected function normalizeMetrics(string $platform, array $rawMetrics): array
    {
        $normalized = [
            'likes' => 0,
            'comments' => 0,
            'shares' => 0,
            'views' => 0,
            'engagement' => 0,
            'clicks' => 0,
            'raw' => $rawMetrics,
        ];

        // Platform-specific metric mapping
        switch ($platform) {
            case 'twitter':
            case 'x':
                $normalized['likes'] = $rawMetrics['likes'] ?? 0;
                $normalized['comments'] = $rawMetrics['replies'] ?? 0;
                $normalized['shares'] = $rawMetrics['retweets'] ?? 0;
                $normalized['views'] = $rawMetrics['impressions'] ?? 0;
                $normalized['engagement'] = ($rawMetrics['likes'] ?? 0) +
                    ($rawMetrics['replies'] ?? 0) +
                    ($rawMetrics['retweets'] ?? 0) +
                    ($rawMetrics['quotes'] ?? 0);
                break;

            case 'youtube':
                $normalized['likes'] = $rawMetrics['like_count'] ?? 0;
                $normalized['comments'] = $rawMetrics['comment_count'] ?? 0;
                $normalized['views'] = $rawMetrics['view_count'] ?? 0;
                $normalized['engagement'] = ($rawMetrics['like_count'] ?? 0) +
                    ($rawMetrics['comment_count'] ?? 0);
                break;

            case 'linkedin':
                $normalized['likes'] = $rawMetrics['num_likes'] ?? 0;
                $normalized['comments'] = $rawMetrics['num_comments'] ?? 0;
                $normalized['shares'] = $rawMetrics['num_shares'] ?? 0;
                $normalized['views'] = $rawMetrics['num_views'] ?? 0;
                $normalized['engagement'] = ($rawMetrics['num_likes'] ?? 0) +
                    ($rawMetrics['num_comments'] ?? 0) +
                    ($rawMetrics['num_shares'] ?? 0);
                break;

            case 'tiktok':
                $normalized['likes'] = $rawMetrics['like_count'] ?? 0;
                $normalized['comments'] = $rawMetrics['comment_count'] ?? 0;
                $normalized['shares'] = $rawMetrics['share_count'] ?? 0;
                $normalized['views'] = $rawMetrics['view_count'] ?? 0;
                $normalized['engagement'] = ($rawMetrics['like_count'] ?? 0) +
                    ($rawMetrics['comment_count'] ?? 0) +
                    ($rawMetrics['share_count'] ?? 0);
                break;

            case 'pinterest':
                $normalized['likes'] = $rawMetrics['save_count'] ?? 0;
                $normalized['comments'] = $rawMetrics['comment_count'] ?? 0;
                $normalized['views'] = $rawMetrics['impression_count'] ?? 0;
                $normalized['clicks'] = $rawMetrics['click_count'] ?? 0;
                break;

            default:
                // Generic mapping
                $normalized['likes'] = $rawMetrics['likes'] ?? $rawMetrics['like_count'] ?? 0;
                $normalized['comments'] = $rawMetrics['comments'] ?? $rawMetrics['comment_count'] ?? 0;
                $normalized['shares'] = $rawMetrics['shares'] ?? $rawMetrics['share_count'] ?? 0;
                $normalized['views'] = $rawMetrics['views'] ?? $rawMetrics['view_count'] ?? 0;
                break;
        }

        return $normalized;
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
