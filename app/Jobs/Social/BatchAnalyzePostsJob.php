<?php

namespace App\Jobs\Social;

use App\Models\Social\SocialPost;
use App\Services\Social\HistoricalContentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Batch Analyze Posts Job
 *
 * Asynchronously analyzes multiple posts in batch with rate limiting.
 */
class BatchAnalyzePostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes
    public $tries = 1; // Don't retry batch jobs

    private array $postIds;
    private array $options;
    private string $orgId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $orgId,
        array $postIds,
        array $options = []
    ) {
        $this->orgId = $orgId;
        $this->postIds = $postIds;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(HistoricalContentService $service): void
    {
        try {
            // Set org context for RLS
            \DB::statement("SET app.current_org_id = '{$this->orgId}'");

            $posts = SocialPost::whereIn('id', $this->postIds)
                ->where('org_id', $this->orgId)
                ->get();

            Log::info('Starting batch analysis', [
                'org_id' => $this->orgId,
                'post_count' => $posts->count(),
            ]);

            $result = $service->batchAnalyze($posts, $this->options);

            Log::info('Batch analysis completed', [
                'org_id' => $this->orgId,
                'total' => $result['total'],
                'analyzed' => $result['analyzed'],
                'failed' => $result['failed'],
                'skipped' => $result['skipped'],
            ]);

        } catch (\Exception $e) {
            Log::error('Batch analysis job failed', [
                'org_id' => $this->orgId,
                'post_count' => count($this->postIds),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Batch analysis job failed permanently', [
            'org_id' => $this->orgId,
            'post_count' => count($this->postIds),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Dispatch batch jobs for pending analysis posts
     */
    public static function dispatchForPendingPosts(
        string $orgId,
        ?string $profileGroupId = null,
        int $batchSize = 50
    ): int {
        $query = SocialPost::where('org_id', $orgId)
            ->where('is_historical', true)
            ->where('is_analyzed', false);

        if ($profileGroupId) {
            $query->where('profile_group_id', $profileGroupId);
        }

        $pendingPosts = $query->pluck('id')->toArray();

        $batches = array_chunk($pendingPosts, $batchSize);
        $dispatched = 0;

        foreach ($batches as $batch) {
            self::dispatch($orgId, $batch, [
                'delay_ms' => 2000, // 2 seconds between posts
            ]);
            $dispatched++;
        }

        Log::info('Dispatched batch analysis jobs', [
            'org_id' => $orgId,
            'profile_group_id' => $profileGroupId,
            'total_posts' => count($pendingPosts),
            'batch_count' => $dispatched,
        ]);

        return $dispatched;
    }
}
