<?php

namespace App\Jobs\AI;

use App\Services\Embedding\EmbeddingOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Process embedding generation for knowledge items, content, or other entities
 *
 * This job handles expensive embedding generation operations asynchronously
 * to prevent blocking HTTP requests and respect Gemini API rate limits.
 */
class ProcessEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes
    public $backoff = [60, 180, 600]; // Exponential backoff

    protected string $orgId;
    protected string $entityType;
    protected string $entityId;
    protected string $content;
    protected array $metadata;
    protected string $jobId;

    /**
     * Create a new job instance
     *
     * @param string $orgId Organization ID for RLS context
     * @param string $entityType Type of entity (knowledge, campaign, content, etc.)
     * @param string $entityId Entity identifier
     * @param string $content Text content to generate embedding for
     * @param array $metadata Additional metadata
     * @param string|null $jobId Unique job identifier for tracking
     */
    public function __construct(
        string $orgId,
        string $entityType,
        string $entityId,
        string $content,
        array $metadata = [],
        ?string $jobId = null
    ) {
        $this->orgId = $orgId;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->content = $content;
        $this->metadata = $metadata;
        $this->jobId = $jobId ?? \Illuminate\Support\Str::uuid()->toString();
        $this->onQueue('embeddings');
    }

    /**
     * Execute the job
     */
    public function handle(EmbeddingOrchestrator $orchestrator): void
    {
        Log::info('Embedding generation job started', [
            'job_id' => $this->jobId,
            'org_id' => $this->orgId,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'content_length' => strlen($this->content),
        ]);

        // Update job status to 'processing'
        $this->updateJobStatus('processing');

        try {
            // Set RLS context for multi-tenancy
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                config('cmis.system_user_id'),
                $this->orgId
            ]);

            // Generate embedding
            $embedding = $orchestrator->generateEmbedding(
                $this->content,
                $this->orgId,
                'RETRIEVAL_DOCUMENT'
            );

            if (!$embedding) {
                throw new \Exception('Embedding generation returned null');
            }

            // Store embedding in database
            $stored = $orchestrator->storeEmbedding(
                $this->entityType,
                $this->entityId,
                $embedding,
                array_merge($this->metadata, [
                    'generated_at' => now()->toISOString(),
                    'job_id' => $this->jobId,
                ])
            );

            if (!$stored) {
                throw new \Exception('Failed to store embedding in database');
            }

            // Store result in cache for retrieval
            $cacheKey = "embedding_job_result:{$this->jobId}";
            Cache::put($cacheKey, [
                'status' => 'completed',
                'entity_type' => $this->entityType,
                'entity_id' => $this->entityId,
                'embedding_dimension' => count($embedding),
                'generated_at' => now()->toISOString(),
            ], now()->addHours(24)); // Keep for 24 hours

            // Update job status to 'completed'
            $this->updateJobStatus('completed', [
                'embedding_dimension' => count($embedding),
            ]);

            Log::info('Embedding generation completed', [
                'job_id' => $this->jobId,
                'entity_type' => $this->entityType,
                'entity_id' => $this->entityId,
                'dimension' => count($embedding),
            ]);

        } catch (\Exception $e) {
            Log::error('Embedding generation failed', [
                'job_id' => $this->jobId,
                'org_id' => $this->orgId,
                'entity_type' => $this->entityType,
                'entity_id' => $this->entityId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Store error in cache
            $cacheKey = "embedding_job_result:{$this->jobId}";
            Cache::put($cacheKey, [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'failed_at' => now()->toISOString(),
            ], now()->addHours(24));

            // Update job status to 'failed'
            $this->updateJobStatus('failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure after all retries
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Embedding generation job failed permanently', [
            'job_id' => $this->jobId,
            'org_id' => $this->orgId,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'error' => $exception->getMessage(),
        ]);

        // Update final status
        $cacheKey = "embedding_job_result:{$this->jobId}";
        Cache::put($cacheKey, [
            'status' => 'failed',
            'error' => 'Job failed after all retries: ' . $exception->getMessage(),
            'failed_at' => now()->toISOString(),
        ], now()->addHours(24));

        $this->updateJobStatus('failed', [
            'error' => 'Job failed after all retries: ' . $exception->getMessage(),
            'retries_exhausted' => true,
        ]);
    }

    /**
     * Update job status in cache
     */
    protected function updateJobStatus(string $status, array $metadata = []): void
    {
        $cacheKey = "embedding_job_status:{$this->jobId}";
        Cache::put($cacheKey, array_merge([
            'job_id' => $this->jobId,
            'status' => $status,
            'org_id' => $this->orgId,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'updated_at' => now()->toISOString(),
        ], $metadata), now()->addHours(24));
    }

    /**
     * Get job ID
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }
}
