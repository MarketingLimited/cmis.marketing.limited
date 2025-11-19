<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

abstract class BasePlatformSyncService
{
    protected $platform;
    protected $integration;
    protected $orgId;

    /**
     * Initialize sync service with integration
     */
    public function __construct($integration)
    {
        $this->integration = $integration;
        $this->orgId = $integration->org_id;
    }

    /**
     * Main sync method - to be implemented by each platform
     */
    abstract public function sync(array $options = []): array;

    /**
     * Sync posts/content from platform
     */
    abstract protected function syncPosts(Carbon $since): int;

    /**
     * Sync metrics/analytics from platform
     */
    abstract protected function syncMetrics(Carbon $since): int;

    /**
     * Sync comments from platform
     */
    abstract protected function syncComments(Carbon $since): int;

    /**
     * Sync messages/inbox from platform
     */
    abstract protected function syncMessages(Carbon $since): int;

    /**
     * Get platform-specific API client
     */
    abstract protected function getApiClient();

    /**
     * Log sync activity
     */
    protected function logSync(string $type, string $status, array $data = [], ?string $error = null): void
    {
        DB::table('cmis.sync_logs')->insert([
            'integration_id' => $this->integration->integration_id,
            'org_id' => $this->orgId,
            'platform' => $this->platform,
            'sync_type' => $type,
            'status' => $status,
            'items_synced' => $data['items_synced'] ?? 0,
            'sync_data' => json_encode($data),
            'error_message' => $error,
            'started_at' => $data['started_at'] ?? now(),
            'completed_at' => now(),
            'created_at' => now(),
        ]);
    }

    /**
     * Get last sync time for a specific type
     */
    protected function getLastSyncTime(string $type): Carbon
    {
        $lastSync = DB::table('cmis.sync_logs')
            ->where('integration_id', $this->integration->integration_id)
            ->where('sync_type', $type)
            ->where('status', 'success')
            ->orderBy('completed_at', 'desc')
            ->first();

        return $lastSync ? Carbon::parse($lastSync->completed_at) : now()->subDays(30);
    }

    /**
     * Check rate limit
     */
    protected function checkRateLimit(string $endpoint): bool
    {
        $key = "rate_limit:{$this->platform}:{$this->orgId}:{$endpoint}";
        $limit = config("services.{$this->platform}.rate_limit", 100);
        $window = config("services.{$this->platform}.rate_window", 3600);

        $current = Cache::get($key, 0);

        if ($current >= $limit) {
            Log::warning("Rate limit exceeded for {$this->platform} - {$endpoint}");
            return false;
        }

        Cache::put($key, $current + 1, $window);
        return true;
    }

    /**
     * Handle API errors with retry logic
     */
    protected function handleApiError(\Exception $e, string $operation): void
    {
        Log::error("API Error in {$this->platform} - {$operation}: " . $e->getMessage(), [
            'platform' => $this->platform,
            'org_id' => $this->orgId,
            'integration_id' => $this->integration->integration_id,
            'trace' => $e->getTraceAsString()
        ]);

        $this->logSync($operation, 'failed', [], $e->getMessage());
    }

    /**
     * Validate access token
     */
    protected function validateAccessToken(): bool
    {
        if (!$this->integration->access_token) {
            Log::warning("No access token for {$this->platform} integration {$this->integration->integration_id}");
            return false;
        }

        // Check if token is expired
        if ($this->integration->token_expires_at && Carbon::parse($this->integration->token_expires_at)->isPast()) {
            Log::warning("Access token expired for {$this->platform} integration {$this->integration->integration_id}");
            return false;
        }

        return true;
    }

    /**
     * Refresh access token if needed
     */
    protected function refreshAccessTokenIfNeeded(): bool
    {
        if (!$this->integration->token_expires_at) {
            return true;
        }

        $expiresAt = Carbon::parse($this->integration->token_expires_at);

        // Refresh if expiring in less than 5 minutes
        if ($expiresAt->diffInMinutes(now()) < 5) {
            try {
                return $this->refreshAccessToken();
            } catch (\Exception $e) {
                Log::error("Failed to refresh token for {$this->platform}: " . $e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * Refresh access token - to be implemented by each platform
     */
    abstract protected function refreshAccessToken(): bool;

    /**
     * Store synced post
     */
    protected function storePost(array $postData): int
    {
        return DB::table('cmis_social.social_posts')->insertGetId([
            'org_id' => $this->orgId,
            'integration_id' => $this->integration->integration_id,
            'platform' => $this->platform,
            'platform_post_id' => $postData['platform_post_id'],
            'post_type' => $postData['post_type'] ?? 'post',
            'content' => $postData['content'] ?? null,
            'media_urls' => json_encode($postData['media_urls'] ?? []),
            'permalink' => $postData['permalink'] ?? null,
            'published_at' => $postData['published_at'] ?? now(),
            'metrics' => json_encode($postData['metrics'] ?? []),
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'post_id');
    }

    /**
     * Store synced comment
     */
    protected function storeComment(array $commentData): int
    {
        return DB::table('cmis_social.social_comments')->insertGetId([
            'org_id' => $this->orgId,
            'post_id' => $commentData['post_id'] ?? null,
            'platform' => $this->platform,
            'platform_comment_id' => $commentData['platform_comment_id'],
            'author_name' => $commentData['author_name'] ?? null,
            'author_id' => $commentData['author_id'] ?? null,
            'comment_text' => $commentData['comment_text'],
            'parent_comment_id' => $commentData['parent_comment_id'] ?? null,
            'sentiment' => $commentData['sentiment'] ?? 'neutral',
            'is_hidden' => $commentData['is_hidden'] ?? false,
            'created_at' => $commentData['created_at'] ?? now(),
            'updated_at' => now(),
        ], 'comment_id');
    }

    /**
     * Store synced message
     */
    protected function storeMessage(array $messageData): int
    {
        return DB::table('cmis_social.social_messages')->insertGetId([
            'org_id' => $this->orgId,
            'integration_id' => $this->integration->integration_id,
            'platform' => $this->platform,
            'platform_message_id' => $messageData['platform_message_id'],
            'conversation_id' => $messageData['conversation_id'] ?? null,
            'sender_id' => $messageData['sender_id'],
            'sender_name' => $messageData['sender_name'] ?? null,
            'message_text' => $messageData['message_text'],
            'message_type' => $messageData['message_type'] ?? 'text',
            'is_from_page' => $messageData['is_from_page'] ?? false,
            'status' => $messageData['status'] ?? 'unread',
            'created_at' => $messageData['created_at'] ?? now(),
            'updated_at' => now(),
        ], 'message_id');
    }

    /**
     * Update metrics for post
     */
    protected function updatePostMetrics(string $platformPostId, array $metrics): bool
    {
        return DB::table('cmis_social.social_posts')
            ->where('platform_post_id', $platformPostId)
            ->where('org_id', $this->orgId)
            ->update([
                'metrics' => json_encode($metrics),
                'updated_at' => now(),
            ]);
    }

    /**
     * Batch process items with chunking
     */
    protected function batchProcess(array $items, callable $callback, int $chunkSize = 50): int
    {
        $processed = 0;
        $chunks = array_chunk($items, $chunkSize);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $item) {
                try {
                    $callback($item);
                    $processed++;
                } catch (\Exception $e) {
                    Log::error("Error processing item in {$this->platform}: " . $e->getMessage());
                }
            }

            // Small delay to respect rate limits
            usleep(100000); // 0.1 second
        }

        return $processed;
    }
}
