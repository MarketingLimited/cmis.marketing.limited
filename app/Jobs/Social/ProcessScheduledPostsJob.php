<?php

namespace App\Jobs\Social;

use App\Services\Social\PublishingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process all scheduled posts that are due for publishing
 *
 * This job is typically dispatched by the scheduler every minute
 * to check for and publish any posts that have reached their scheduled time
 */
class ProcessScheduledPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Execute the job.
     */
    public function handle(PublishingService $publishingService): void
    {
        try {
            Log::info('Processing scheduled posts queue');

            // Process all due posts in the queue
            $results = $publishingService->processQueue();

            Log::info('Scheduled posts processing completed', [
                'success' => $results['success'],
                'failed' => $results['failed'],
                'total' => $results['success'] + $results['failed'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process scheduled posts', [
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
        Log::error('ProcessScheduledPostsJob failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
