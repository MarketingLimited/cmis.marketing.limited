<?php

namespace App\Jobs;

use App\Models\Creative\ContentItem;
use App\Services\PublishingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishScheduledPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 60;

    protected $contentItem;

    /**
     * Create a new job instance.
     */
    public function __construct(ContentItem $contentItem)
    {
        $this->contentItem = $contentItem;
        $this->onQueue('publishing');
    }

    /**
     * Execute the job.
     */
    public function handle(PublishingService $publishingService): void
    {
        Log::info('Publishing scheduled post', [
            'content_id' => $this->contentItem->content_id,
            'scheduled_for' => $this->contentItem->scheduled_for,
        ]);

        // Check if content is still scheduled
        if ($this->contentItem->status !== 'scheduled') {
            Log::warning('Content is no longer scheduled, skipping', [
                'content_id' => $this->contentItem->content_id,
                'current_status' => $this->contentItem->status,
            ]);
            return;
        }

        // Check if scheduled time has arrived
        if ($this->contentItem->scheduled_for && $this->contentItem->scheduled_for->isFuture()) {
            Log::warning('Scheduled time not yet arrived, re-queuing', [
                'content_id' => $this->contentItem->content_id,
                'scheduled_for' => $this->contentItem->scheduled_for,
            ]);

            $delay = $this->contentItem->scheduled_for->diffInSeconds(now());
            $this->release($delay);
            return;
        }

        try {
            $post = $publishingService->publishContent($this->contentItem);

            if ($post) {
                Log::info('Post published successfully', [
                    'content_id' => $this->contentItem->content_id,
                    'post_id' => $post->post_id,
                    'external_id' => $post->external_post_id,
                ]);
            } else {
                throw new \Exception('Publishing service returned null');
            }
        } catch (\Exception $e) {
            Log::error('Failed to publish scheduled post', [
                'content_id' => $this->contentItem->content_id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Update content status to failed
            $this->contentItem->update(['status' => 'failed']);

            if ($this->attempts() < $this->tries) {
                $this->release(30);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Publish job failed permanently', [
            'content_id' => $this->contentItem->content_id,
            'error' => $exception->getMessage(),
        ]);

        $this->contentItem->update(['status' => 'failed']);
    }
}
