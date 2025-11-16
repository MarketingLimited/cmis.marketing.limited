<?php

namespace App\Jobs;

use App\Models\Creative\ContentPlan;
use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAIContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $contentPlanId,
        public string $prompt,
        public string $type,
        public array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AIService $aiService): void
    {
        Log::info('Starting AI content generation', [
            'content_plan_id' => $this->contentPlanId,
            'type' => $this->type,
        ]);

        try {
            $contentPlan = ContentPlan::findOrFail($this->contentPlanId);

            // Update status to generating
            $contentPlan->update(['status' => 'generating']);

            // Generate content using AI service
            $result = $aiService->generate($this->prompt, $this->type, $this->options);

            // Update content plan with generated content
            $contentPlan->update([
                'generated_content' => $result['content'] ?? null,
                'ai_metadata' => array_merge(
                    $contentPlan->ai_metadata ?? [],
                    [
                        'generated_at' => now()->toISOString(),
                        'model' => $result['model'] ?? 'unknown',
                        'tokens_used' => $result['tokens_used'] ?? 0,
                    ]
                ),
                'status' => 'generated',
            ]);

            Log::info('AI content generation completed', [
                'content_plan_id' => $this->contentPlanId,
                'tokens_used' => $result['tokens_used'] ?? 0,
            ]);

        } catch (\Exception $e) {
            Log::error('AI content generation failed', [
                'content_plan_id' => $this->contentPlanId,
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
        Log::error('AI content generation job failed after all retries', [
            'content_plan_id' => $this->contentPlanId,
            'error' => $exception->getMessage(),
        ]);

        try {
            $contentPlan = ContentPlan::find($this->contentPlanId);
            if ($contentPlan) {
                $contentPlan->update([
                    'status' => 'failed',
                    'ai_metadata' => array_merge(
                        $contentPlan->ai_metadata ?? [],
                        [
                            'failed_at' => now()->toISOString(),
                            'error' => $exception->getMessage(),
                        ]
                    ),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update content plan status after job failure', [
                'content_plan_id' => $this->contentPlanId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
