<?php

namespace App\Jobs;

use App\Models\AI\GeneratedMedia;
use App\Services\AI\VeoVideoService;
use App\Services\AI\AiQuotaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $generatedMediaId,
        private string $orgId,
        private string $userId,
        private array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(VeoVideoService $veoService, AiQuotaService $quotaService): void
    {
        try {
            // Set RLS context
            DB::statement("SELECT cmis.init_transaction_context(?)", [$this->orgId]);

            // Get the generated media record
            $media = GeneratedMedia::findOrFail($this->generatedMediaId);

            // Mark as processing
            $media->markAsProcessing();

            // Determine generation method
            if (!empty($this->options['source_image'])) {
                $result = $veoService->imageToVideo(
                    imagePath: $this->options['source_image'],
                    animationPrompt: $media->prompt_text,
                    duration: $this->options['duration'] ?? 6,
                    aspectRatio: $this->options['aspect_ratio'] ?? '16:9',
                    orgId: $this->orgId
                );
            } elseif (!empty($this->options['reference_images'])) {
                $result = $veoService->generateWithReferenceImages(
                    prompt: $media->prompt_text,
                    referenceImagePaths: $this->options['reference_images'],
                    duration: $this->options['duration'] ?? 7,
                    aspectRatio: $this->options['aspect_ratio'] ?? '16:9',
                    orgId: $this->orgId
                );
            } else {
                $result = $veoService->generateFromText(
                    prompt: $media->prompt_text,
                    duration: $this->options['duration'] ?? 7,
                    aspectRatio: $this->options['aspect_ratio'] ?? '16:9',
                    useFastModel: $this->options['use_fast_model'] ?? false,
                    orgId: $this->orgId
                );
            }

            // Update media record with results
            $media->update([
                'status' => GeneratedMedia::STATUS_COMPLETED,
                'media_url' => $result['url'],
                'storage_path' => $result['storage_path'],
                'duration_seconds' => $result['duration'],
                'aspect_ratio' => $result['aspect_ratio'],
                'file_size_bytes' => $result['file_size'],
                'generation_cost' => $result['cost'],
                'metadata' => array_merge($media->metadata ?? [], [
                    'gcs_uri' => $result['gcs_uri'] ?? null,
                    'model' => $result['model'],
                    'generation_options' => $this->options,
                    'completed_at' => now()->toIso8601String()
                ])
            ]);

            // Record quota usage
            $quotaService->recordUsage(
                orgId: $this->orgId,
                userId: $this->userId,
                modelType: 'veo',
                operationType: 'video_generation',
                tokensUsed: 0, // Veo doesn't use token-based pricing
                metadata: [
                    'media_id' => $media->id,
                    'duration' => $result['duration'],
                    'cost' => $result['cost']
                ]
            );

            Log::info('Video generation completed', [
                'media_id' => $media->id,
                'org_id' => $this->orgId,
                'duration' => $result['duration']
            ]);

        } catch (Exception $e) {
            Log::error('Video generation job failed', [
                'media_id' => $this->generatedMediaId,
                'org_id' => $this->orgId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Mark media as failed
            $media = GeneratedMedia::find($this->generatedMediaId);
            if ($media) {
                $media->markAsFailed($e->getMessage());
            }

            // Re-throw to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Video generation job permanently failed', [
            'media_id' => $this->generatedMediaId,
            'org_id' => $this->orgId,
            'error' => $exception->getMessage()
        ]);

        // Mark media as failed
        DB::statement("SELECT cmis.init_transaction_context(?)", [$this->orgId]);
        $media = GeneratedMedia::find($this->generatedMediaId);
        if ($media) {
            $media->markAsFailed('Job failed after ' . $this->tries . ' attempts: ' . $exception->getMessage());
        }
    }
}
