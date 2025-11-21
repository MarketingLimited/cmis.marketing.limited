<?php

namespace App\Jobs\AI;

use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Generic AI content generation job for queuing expensive AI operations
 *
 * This job handles AI content generation requests asynchronously to prevent
 * blocking HTTP requests and respect rate limits.
 */
class GenerateContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 180; // 3 minutes
    public $backoff = [60, 120, 300]; // Exponential backoff

    protected string $orgId;
    protected string $userId;
    protected string $prompt;
    protected string $contentType;
    protected array $options;
    protected string $jobId;

    /**
     * Create a new job instance
     *
     * @param string $orgId Organization ID for RLS context
     * @param string $userId User ID who requested the generation
     * @param string $prompt The prompt for AI generation
     * @param string $contentType Type of content (campaign, ad_copy, social_post, etc.)
     * @param array $options Additional options (language, tone, max_tokens, etc.)
     * @param string|null $jobId Unique job identifier for tracking
     */
    public function __construct(
        string $orgId,
        string $userId,
        string $prompt,
        string $contentType,
        array $options = [],
        ?string $jobId = null
    ) {
        $this->orgId = $orgId;
        $this->userId = $userId;
        $this->prompt = $prompt;
        $this->contentType = $contentType;
        $this->options = $options;
        $this->jobId = $jobId ?? \Illuminate\Support\Str::uuid()->toString();
        $this->onQueue('ai-generation');
    }

    /**
     * Execute the job
     */
    public function handle(AIService $aiService): void
    {
        Log::info('AI content generation job started', [
            'job_id' => $this->jobId,
            'org_id' => $this->orgId,
            'user_id' => $this->userId,
            'content_type' => $this->contentType,
        ]);

        // Update job status to 'processing'
        $this->updateJobStatus('processing');

        try {
            // Set RLS context for multi-tenancy
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $this->userId,
                $this->orgId
            ]);

            // Generate content using AI service
            $result = $aiService->generate(
                $this->prompt,
                $this->contentType,
                $this->options
            );

            // Store result in cache for retrieval
            $cacheKey = "ai_job_result:{$this->jobId}";
            Cache::put($cacheKey, [
                'status' => 'completed',
                'content' => $result['content'] ?? null,
                'model' => $result['model'] ?? 'unknown',
                'tokens_used' => $result['tokens_used'] ?? 0,
                'generated_at' => now()->toISOString(),
            ], now()->addHours(24)); // Keep for 24 hours

            // Update job status to 'completed'
            $this->updateJobStatus('completed', [
                'tokens_used' => $result['tokens_used'] ?? 0,
            ]);

            Log::info('AI content generation completed', [
                'job_id' => $this->jobId,
                'tokens_used' => $result['tokens_used'] ?? 0,
            ]);

        } catch (\Exception $e) {
            Log::error('AI content generation failed', [
                'job_id' => $this->jobId,
                'org_id' => $this->orgId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Store error in cache
            $cacheKey = "ai_job_result:{$this->jobId}";
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
        Log::error('AI content generation job failed permanently', [
            'job_id' => $this->jobId,
            'org_id' => $this->orgId,
            'error' => $exception->getMessage(),
        ]);

        // Update final status
        $cacheKey = "ai_job_result:{$this->jobId}";
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
        $cacheKey = "ai_job_status:{$this->jobId}";
        Cache::put($cacheKey, array_merge([
            'job_id' => $this->jobId,
            'status' => $status,
            'org_id' => $this->orgId,
            'user_id' => $this->userId,
            'content_type' => $this->contentType,
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
