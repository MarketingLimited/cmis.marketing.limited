<?php

namespace App\Jobs\Social;

use App\Jobs\Social\CheckTikTokPublishStatusJob;
use App\Models\Social\SocialPost;
use App\Services\Social\Publishers\PublisherFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job for publishing social posts asynchronously.
 *
 * This job handles the actual publishing to social platforms,
 * allowing the HTTP request to return immediately while
 * the publishing happens in the background.
 */
class PublishSocialPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 10;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 180;

    protected string $postId;
    protected string $orgId;
    protected string $platform;
    protected string $content;
    protected array $media;
    protected array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $postId,
        string $orgId,
        string $platform,
        string $content,
        array $media = [],
        array $options = []
    ) {
        $this->postId = $postId;
        $this->orgId = $orgId;
        $this->platform = $platform;
        $this->content = $content;
        $this->media = $media;
        $this->options = $options;

        // Use the social-publishing queue
        $this->onQueue('social-publishing');
    }

    /**
     * Execute the job.
     */
    public function handle(PublisherFactory $publisherFactory): void
    {
        Log::info('PublishSocialPostJob started', [
            'post_id' => $this->postId,
            'platform' => $this->platform,
            'org_id' => $this->orgId,
        ]);

        $startTime = microtime(true);

        try {
            // Find the post
            $post = SocialPost::where('id', $this->postId)
                ->where('org_id', $this->orgId)
                ->first();

            if (!$post) {
                Log::error('PublishSocialPostJob: Post not found', [
                    'post_id' => $this->postId,
                ]);
                return;
            }

            // Skip if already published or failed
            if (in_array($post->status, ['published', 'failed'])) {
                Log::info('PublishSocialPostJob: Post already processed', [
                    'post_id' => $this->postId,
                    'status' => $post->status,
                ]);
                return;
            }

            // Update status to publishing
            $post->update(['status' => 'publishing']);

            // Get the publisher
            $publisher = $publisherFactory->getPublisher($this->platform, $this->orgId);

            if (!$publisher || !$publisher->hasActiveConnection()) {
                $this->markFailed($post, "No active {$this->platform} connection found");
                return;
            }

            // Publish to platform
            $result = $publisher->publish($this->content, $this->media, $this->options);

            $elapsed = round((microtime(true) - $startTime) * 1000);

            if ($result['success']) {
                // TikTok processes videos asynchronously - don't mark as published yet
                if ($this->platform === 'tiktok' && !empty($result['post_id'])) {
                    $post->update([
                        'status' => 'processing', // TikTok is still processing
                        'post_external_id' => $result['post_id'],
                        'permalink' => $result['permalink'] ?? null,
                        'metadata' => array_merge($post->metadata ?? [], [
                            'publish_duration_ms' => $elapsed,
                            'published_via' => 'queue',
                            'tiktok_publish_id' => $result['post_id'],
                            'tiktok_status_pending' => true,
                        ]),
                    ]);

                    // Dispatch job to check TikTok status after processing
                    CheckTikTokPublishStatusJob::dispatch(
                        $this->postId,
                        $this->orgId,
                        $result['post_id'] // This is the publish_id from TikTok
                    );

                    Log::info('PublishSocialPostJob: TikTok upload complete, status check scheduled', [
                        'post_id' => $this->postId,
                        'platform' => $this->platform,
                        'duration_ms' => $elapsed,
                        'tiktok_publish_id' => $result['post_id'],
                    ]);
                } else {
                    // Other platforms - mark as published immediately
                    $post->update([
                        'status' => 'published',
                        'published_at' => now(),
                        'post_external_id' => $result['post_id'] ?? null,
                        'permalink' => $result['permalink'] ?? null,
                        'metadata' => array_merge($post->metadata ?? [], [
                            'publish_duration_ms' => $elapsed,
                            'published_via' => 'queue',
                        ]),
                    ]);

                    Log::info('PublishSocialPostJob: Published successfully', [
                        'post_id' => $this->postId,
                        'platform' => $this->platform,
                        'duration_ms' => $elapsed,
                        'external_id' => $result['post_id'] ?? null,
                    ]);
                }
            } else {
                $this->markFailed($post, $result['message'] ?? 'Unknown error', $elapsed);
            }
        } catch (\Exception $e) {
            Log::error('PublishSocialPostJob: Exception', [
                'post_id' => $this->postId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Mark the post as failed.
     */
    protected function markFailed(SocialPost $post, string $message, ?int $durationMs = null): void
    {
        $metadata = $post->metadata ?? [];
        $metadata['last_error'] = $message;
        $metadata['failed_via'] = 'queue';

        if ($durationMs !== null) {
            $metadata['publish_duration_ms'] = $durationMs;
        }

        $post->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $message,
            'metadata' => $metadata,
        ]);

        Log::warning('PublishSocialPostJob: Publishing failed', [
            'post_id' => $this->postId,
            'platform' => $this->platform,
            'error' => $message,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PublishSocialPostJob: Job failed permanently', [
            'post_id' => $this->postId,
            'platform' => $this->platform,
            'error' => $exception->getMessage(),
        ]);

        // Update post status on final failure
        $post = SocialPost::find($this->postId);
        if ($post && $post->status !== 'published') {
            $post->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => 'Publishing failed after multiple attempts: ' . $exception->getMessage(),
            ]);
        }
    }
}
