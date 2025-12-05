<?php

namespace App\Jobs\Social;

use App\Models\Platform\PlatformConnection;
use App\Models\Social\SocialPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Job for checking TikTok video publish status.
 *
 * TikTok processes videos asynchronously after upload. This job polls
 * the TikTok API to get the actual publish status and updates the post
 * record accordingly.
 *
 * Status values from TikTok:
 * - PROCESSING_UPLOAD: Video is being uploaded
 * - PROCESSING_DOWNLOAD: Video is being downloaded (for PULL method)
 * - SEND_TO_USER_INBOX: Waiting for user approval (direct post)
 * - PUBLISH_COMPLETE: Successfully published
 * - FAILED: Processing/publishing failed
 */
class CheckTikTokPublishStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     * We poll multiple times because TikTok processing can take time.
     */
    public int $tries = 10;

    /**
     * The number of seconds to wait before retrying.
     * Exponential backoff: 5s, 10s, 15s, etc.
     */
    public int $backoff = 5;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 30;

    protected string $postId;
    protected string $orgId;
    protected string $publishId;
    protected int $checkAttempt;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $postId,
        string $orgId,
        string $publishId,
        int $checkAttempt = 1
    ) {
        $this->postId = $postId;
        $this->orgId = $orgId;
        $this->publishId = $publishId;
        $this->checkAttempt = $checkAttempt;

        // Use same queue as publishing
        $this->onQueue('social-publishing');

        // Delay first check by 5 seconds to allow TikTok time to start processing
        if ($checkAttempt === 1) {
            $this->delay(now()->addSeconds(5));
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('CheckTikTokPublishStatusJob: Checking status', [
            'post_id' => $this->postId,
            'publish_id' => $this->publishId,
            'attempt' => $this->checkAttempt,
        ]);

        try {
            // Set org context
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$this->orgId]);

            // Find the post
            $post = SocialPost::where('id', $this->postId)
                ->where('org_id', $this->orgId)
                ->first();

            if (!$post) {
                Log::warning('CheckTikTokPublishStatusJob: Post not found', [
                    'post_id' => $this->postId,
                ]);
                return;
            }

            // Skip if already definitively failed or not in a pending state
            if ($post->status === 'failed' && $post->error_message && !str_contains($post->error_message, 'pending')) {
                Log::info('CheckTikTokPublishStatusJob: Post already marked as failed', [
                    'post_id' => $this->postId,
                    'error' => $post->error_message,
                ]);
                return;
            }

            // Get TikTok connection
            $connection = PlatformConnection::where('org_id', $this->orgId)
                ->where('platform', 'tiktok')
                ->where('status', 'active')
                ->first();

            if (!$connection) {
                Log::error('CheckTikTokPublishStatusJob: No TikTok connection found', [
                    'post_id' => $this->postId,
                ]);
                return;
            }

            // Call TikTok API to check publish status
            $accessToken = $connection->access_token;
            $result = $this->checkTikTokStatus($accessToken);

            Log::info('CheckTikTokPublishStatusJob: TikTok status response', [
                'post_id' => $this->postId,
                'publish_id' => $this->publishId,
                'status' => $result['status'] ?? 'unknown',
                'fail_reason' => $result['fail_reason'] ?? null,
            ]);

            // Handle the status
            switch ($result['status'] ?? 'unknown') {
                case 'PUBLISH_COMPLETE':
                    $this->markPublished($post, $result);
                    break;

                case 'FAILED':
                    $this->markFailed($post, $result['fail_reason'] ?? 'Unknown TikTok processing error');
                    break;

                case 'PROCESSING_UPLOAD':
                case 'PROCESSING_DOWNLOAD':
                case 'SEND_TO_USER_INBOX':
                    // Still processing, check again later
                    if ($this->checkAttempt < 10) {
                        $this->scheduleNextCheck($post);
                    } else {
                        // Max attempts reached, mark as pending verification
                        $this->markPending($post, $result['status']);
                    }
                    break;

                default:
                    Log::warning('CheckTikTokPublishStatusJob: Unknown status', [
                        'post_id' => $this->postId,
                        'status' => $result['status'] ?? 'null',
                        'full_result' => $result,
                    ]);

                    if ($this->checkAttempt < 10) {
                        $this->scheduleNextCheck($post);
                    }
                    break;
            }
        } catch (\Exception $e) {
            Log::error('CheckTikTokPublishStatusJob: Exception', [
                'post_id' => $this->postId,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry if we haven't exceeded attempts
            if ($this->checkAttempt < 10) {
                throw $e;
            }
        }
    }

    /**
     * Check TikTok publish status via API.
     */
    protected function checkTikTokStatus(string $accessToken): array
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json; charset=UTF-8',
            ])
            ->post('https://open.tiktokapis.com/v2/post/publish/status/fetch/', [
                'publish_id' => $this->publishId,
            ]);

        $json = $response->json();

        Log::debug('CheckTikTokPublishStatusJob: Raw API response', [
            'post_id' => $this->postId,
            'http_status' => $response->status(),
            'body' => $json,
        ]);

        if (!$response->successful()) {
            return [
                'status' => 'API_ERROR',
                'fail_reason' => $json['error']['message'] ?? 'API request failed',
            ];
        }

        return [
            'status' => $json['data']['status'] ?? 'unknown',
            'fail_reason' => $json['data']['fail_reason'] ?? null,
            'publicaly_available_post_id' => $json['data']['publicaly_available_post_id'] ?? null,
        ];
    }

    /**
     * Mark post as successfully published.
     */
    protected function markPublished(SocialPost $post, array $result): void
    {
        $metadata = $post->metadata ?? [];
        $metadata['tiktok_status_verified'] = true;
        $metadata['tiktok_final_status'] = 'PUBLISH_COMPLETE';
        $metadata['status_verified_at'] = now()->toISOString();

        // Update the post
        $post->update([
            'status' => 'published',
            'published_at' => $post->published_at ?? now(),
            'metadata' => $metadata,
            'error_message' => null, // Clear any previous errors
        ]);

        // If TikTok returned the actual post ID, update external_id
        if (!empty($result['publicaly_available_post_id'])) {
            $post->update([
                'post_external_id' => $result['publicaly_available_post_id'],
                'permalink' => 'https://www.tiktok.com/@' . $this->getUsername() . '/video/' . $result['publicaly_available_post_id'],
            ]);
        }

        Log::info('CheckTikTokPublishStatusJob: Post confirmed published', [
            'post_id' => $this->postId,
            'tiktok_post_id' => $result['publicaly_available_post_id'] ?? null,
        ]);
    }

    /**
     * Mark post as failed.
     */
    protected function markFailed(SocialPost $post, string $reason): void
    {
        $friendlyMessage = $this->getFriendlyErrorMessage($reason);

        $metadata = $post->metadata ?? [];
        $metadata['tiktok_status_verified'] = true;
        $metadata['tiktok_final_status'] = 'FAILED';
        $metadata['tiktok_fail_reason'] = $reason;
        $metadata['status_verified_at'] = now()->toISOString();

        $post->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $friendlyMessage,
            'published_at' => null,
            'metadata' => $metadata,
        ]);

        Log::warning('CheckTikTokPublishStatusJob: Post confirmed failed', [
            'post_id' => $this->postId,
            'reason' => $reason,
            'friendly_message' => $friendlyMessage,
        ]);
    }

    /**
     * Mark post as pending (still processing after max attempts).
     */
    protected function markPending(SocialPost $post, string $tiktokStatus): void
    {
        $metadata = $post->metadata ?? [];
        $metadata['tiktok_last_status'] = $tiktokStatus;
        $metadata['status_check_attempts'] = $this->checkAttempt;
        $metadata['last_status_check'] = now()->toISOString();

        // Keep as published but note it's pending verification
        $post->update([
            'metadata' => $metadata,
        ]);

        Log::info('CheckTikTokPublishStatusJob: Post still processing, max checks reached', [
            'post_id' => $this->postId,
            'tiktok_status' => $tiktokStatus,
            'attempts' => $this->checkAttempt,
        ]);
    }

    /**
     * Schedule the next status check.
     */
    protected function scheduleNextCheck(SocialPost $post): void
    {
        // Exponential backoff: 5s, 10s, 15s, 20s, etc.
        $delaySeconds = min(5 + ($this->checkAttempt * 5), 60);

        self::dispatch(
            $this->postId,
            $this->orgId,
            $this->publishId,
            $this->checkAttempt + 1
        )->delay(now()->addSeconds($delaySeconds));

        // Update metadata with check info
        $metadata = $post->metadata ?? [];
        $metadata['status_check_attempts'] = $this->checkAttempt;
        $metadata['last_status_check'] = now()->toISOString();
        $post->update(['metadata' => $metadata]);

        Log::info('CheckTikTokPublishStatusJob: Scheduled next check', [
            'post_id' => $this->postId,
            'next_attempt' => $this->checkAttempt + 1,
            'delay_seconds' => $delaySeconds,
        ]);
    }

    /**
     * Get friendly error message for TikTok fail reasons.
     */
    protected function getFriendlyErrorMessage(string $reason): string
    {
        return match ($reason) {
            'picture_size_check_failed' => 'TikTok rejected video: Resolution too small. Minimum required is 540x960 pixels.',
            'video_duration_check_failed' => 'TikTok rejected video: Duration must be between 3 seconds and 10 minutes.',
            'video_format_check_failed' => 'TikTok rejected video: Invalid format. Use MP4 or MOV with H.264 codec.',
            'video_size_check_failed' => 'TikTok rejected video: File size exceeds 4GB limit.',
            'frame_rate_check_failed' => 'TikTok rejected video: Invalid frame rate. Use 24-60 FPS.',
            'upload_expire' => 'TikTok upload expired. Please try publishing again.',
            'privacy_level_check_failed' => 'TikTok rejected privacy setting. Your app may not have permission for this privacy level.',
            'sound_check_failed' => 'TikTok rejected audio. Please check audio format.',
            'session_expired' => 'TikTok session expired. Please reconnect your TikTok account.',
            default => "TikTok processing failed: {$reason}",
        };
    }

    /**
     * Get the TikTok username (for building permalinks).
     */
    protected function getUsername(): string
    {
        $connection = PlatformConnection::where('org_id', $this->orgId)
            ->where('platform', 'tiktok')
            ->where('status', 'active')
            ->first();

        return $connection?->account_name ?? 'unknown';
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CheckTikTokPublishStatusJob: Job failed permanently', [
            'post_id' => $this->postId,
            'publish_id' => $this->publishId,
            'error' => $exception->getMessage(),
        ]);
    }
}
