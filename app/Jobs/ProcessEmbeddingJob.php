<?php

namespace App\Jobs;

use App\Models\Knowledge\EmbeddingUpdateQueue;
use App\Services\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [10, 30, 60];

    protected $queueItem;

    /**
     * Create a new job instance.
     */
    public function __construct(EmbeddingUpdateQueue $queueItem)
    {
        $this->queueItem = $queueItem;
        $this->onQueue('embeddings');
    }

    /**
     * Execute the job.
     */
    public function handle(EmbeddingService $embeddingService): void
    {
        Log::info('Processing embedding job', [
            'queue_id' => $this->queueItem->queue_id,
            'source_type' => $this->queueItem->source_type,
        ]);

        $this->queueItem->markProcessing();

        try {
            $result = $embeddingService->indexContent(
                $this->queueItem->content,
                $this->queueItem->source_type,
                $this->queueItem->source_id,
                $this->queueItem->metadata ?? []
            );

            if ($result) {
                $this->queueItem->markCompleted();

                Log::info('Embedding processed successfully', [
                    'queue_id' => $this->queueItem->queue_id,
                    'knowledge_id' => $result->knowledge_id,
                ]);
            } else {
                throw new \Exception('Failed to index content');
            }
        } catch (\Exception $e) {
            Log::error('Embedding processing failed', [
                'queue_id' => $this->queueItem->queue_id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            $this->queueItem->markFailed($e->getMessage());

            // Retry if under limit
            if ($this->attempts() < $this->tries) {
                $this->queueItem->resetForRetry();
                $this->release($this->backoff[$this->attempts() - 1] ?? 60);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Embedding job failed permanently', [
            'queue_id' => $this->queueItem->queue_id,
            'error' => $exception->getMessage(),
        ]);

        $this->queueItem->markFailed($exception->getMessage());
    }
}
