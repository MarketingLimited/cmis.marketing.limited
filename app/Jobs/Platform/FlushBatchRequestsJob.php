<?php

namespace App\Jobs\Platform;

use App\Services\Platform\BatchQueueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * FlushBatchRequestsJob
 *
 * Flushes pending batch requests for a specific platform.
 * Scheduled to run at platform-specific intervals:
 * - Meta: Every 5 minutes
 * - Google: Every 10 minutes
 * - TikTok: Every 10 minutes
 * - LinkedIn: Every 30 minutes
 * - Twitter: Every 5 minutes
 * - Snapchat: Every 10 minutes
 */
class FlushBatchRequestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The platform to flush requests for
     */
    public string $platform;

    /**
     * Maximum requests to process per execution
     */
    public int $limit;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(string $platform, int $limit = 100)
    {
        $this->platform = $platform;
        $this->limit = $limit;
        $this->onQueue('platform-batch');
    }

    /**
     * Execute the job.
     */
    public function handle(BatchQueueService $batchQueueService): void
    {
        Log::info("FlushBatchRequestsJob: Starting flush for platform", [
            'platform' => $this->platform,
            'limit' => $this->limit,
        ]);

        try {
            $results = $batchQueueService->flush($this->platform, $this->limit);

            Log::info("FlushBatchRequestsJob: Flush completed", [
                'platform' => $this->platform,
                'processed' => $results['processed'],
                'success' => $results['success'],
                'failed' => $results['failed'],
                'skipped' => $results['skipped'],
            ]);

            // If there are more pending requests, dispatch another job
            if ($results['processed'] >= $this->limit) {
                $stats = $batchQueueService->getQueueStats($this->platform);

                if ($stats['pending'] > 0) {
                    Log::info("FlushBatchRequestsJob: More requests pending, dispatching follow-up", [
                        'platform' => $this->platform,
                        'pending' => $stats['pending'],
                    ]);

                    // Delay slightly to prevent hammering
                    self::dispatch($this->platform, $this->limit)
                        ->delay(now()->addSeconds(30));
                }
            }
        } catch (\Exception $e) {
            Log::error("FlushBatchRequestsJob: Failed", [
                'platform' => $this->platform,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("FlushBatchRequestsJob: Job failed permanently", [
            'platform' => $this->platform,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Determine the tags that should be applied to the job.
     */
    public function tags(): array
    {
        return [
            'platform:' . $this->platform,
            'batch-flush',
        ];
    }
}
