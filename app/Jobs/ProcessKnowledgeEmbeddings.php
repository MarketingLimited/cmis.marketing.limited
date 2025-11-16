<?php

namespace App\Jobs;

use App\Models\Knowledge\KnowledgeBase;
use App\Models\Knowledge\KnowledgeEmbedding;
use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessKnowledgeEmbeddings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $knowledgeId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AIService $aiService): void
    {
        Log::info('Processing knowledge embeddings', [
            'knowledge_id' => $this->knowledgeId,
        ]);

        try {
            $knowledge = KnowledgeBase::findOrFail($this->knowledgeId);

            // Prepare text for embedding
            $text = $this->prepareTextForEmbedding($knowledge);

            // Generate embedding using AI service
            $embedding = $aiService->generateEmbedding($text);

            // Store or update embedding
            KnowledgeEmbedding::updateOrCreate(
                ['knowledge_id' => $knowledge->id],
                [
                    'embedding' => $embedding['vector'],
                    'model' => $embedding['model'] ?? 'text-embedding-ada-002',
                    'dimensions' => $embedding['dimensions'] ?? 768,
                    'processed_at' => now(),
                ]
            );

            // Update knowledge base status
            $knowledge->update([
                'embedding_status' => 'processed',
                'embedding_processed_at' => now(),
            ]);

            Log::info('Knowledge embeddings processed successfully', [
                'knowledge_id' => $this->knowledgeId,
            ]);

        } catch (\Exception $e) {
            Log::error('Knowledge embeddings processing failed', [
                'knowledge_id' => $this->knowledgeId,
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
        Log::error('Knowledge embeddings job failed after all retries', [
            'knowledge_id' => $this->knowledgeId,
            'error' => $exception->getMessage(),
        ]);

        try {
            $knowledge = KnowledgeBase::find($this->knowledgeId);
            if ($knowledge) {
                $knowledge->update([
                    'embedding_status' => 'failed',
                    'embedding_error' => $exception->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update knowledge status after job failure', [
                'knowledge_id' => $this->knowledgeId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Prepare text for embedding generation.
     */
    private function prepareTextForEmbedding(KnowledgeBase $knowledge): string
    {
        $parts = [];

        if ($knowledge->title) {
            $parts[] = $knowledge->title;
        }

        if ($knowledge->summary) {
            $parts[] = $knowledge->summary;
        }

        if ($knowledge->content) {
            // Truncate content to avoid token limits
            $content = substr($knowledge->content, 0, 8000);
            $parts[] = $content;
        }

        if ($knowledge->tags) {
            $parts[] = 'Tags: ' . implode(', ', $knowledge->tags);
        }

        return implode("\n\n", $parts);
    }
}
