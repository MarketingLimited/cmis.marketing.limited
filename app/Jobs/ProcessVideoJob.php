<?php

namespace App\Jobs;

use App\Models\Social\MediaAsset;
use App\Services\Media\VideoProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * Process Video Job
 *
 * Handles background video processing: conversion to H.264/MP4
 * format and thumbnail extraction for social media compatibility.
 */
class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes for large videos

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $assetId,
        private string $orgId,
        private string $originalPath
    ) {}

    /**
     * Execute the job.
     */
    public function handle(VideoProcessingService $processingService): void
    {
        try {
            // Set RLS context for multi-tenancy
            DB::statement("SELECT cmis.init_transaction_context(?)", [$this->orgId]);

            // Get the media asset record
            $asset = MediaAsset::findOrFail($this->assetId);

            Log::info('Starting video processing job', [
                'asset_id' => $this->assetId,
                'org_id' => $this->orgId,
                'original_path' => $this->originalPath,
            ]);

            // Mark as processing
            $asset->update([
                'analysis_status' => 'processing',
            ]);

            // Get full path to original file
            $originalFullPath = Storage::disk('public')->path($this->originalPath);

            if (!file_exists($originalFullPath)) {
                throw new Exception("Original video file not found: {$this->originalPath}");
            }

            // Process the video
            $result = $processingService->processVideo($originalFullPath, $this->orgId);

            // Update the media asset with processing results
            $asset->update([
                'analysis_status' => 'completed',
                'is_analyzed' => true,
                'analyzed_at' => now(),
                'width' => $result['width'],
                'height' => $result['height'],
                'duration_seconds' => (int) $result['duration'],
                'aspect_ratio' => $result['width'] > 0 ? round($result['width'] / $result['height'], 2) : null,
                'file_size' => $result['file_size'],
                'analysis_error' => null,
                'metadata' => array_merge($asset->metadata ?? [], [
                    'processed_url' => $result['processed_url'],
                    'processed_path' => $result['processed_path'],
                    'thumbnail_url' => $result['thumbnail_url'],
                    'thumbnail_path' => $result['thumbnail_path'],
                    'thumbnail_time' => $result['thumbnail_time'],
                    'codec' => $result['codec'],
                    'format' => $result['format'],
                    'transcoded' => $result['transcoded'],
                    'original_codec' => $result['original_codec'],
                    'processed_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::info('Video processing completed successfully', [
                'asset_id' => $this->assetId,
                'org_id' => $this->orgId,
                'transcoded' => $result['transcoded'],
                'duration' => $result['duration'],
            ]);

        } catch (Exception $e) {
            Log::error('Video processing job failed', [
                'asset_id' => $this->assetId,
                'org_id' => $this->orgId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update asset with error status
            try {
                DB::statement("SELECT cmis.init_transaction_context(?)", [$this->orgId]);
                $asset = MediaAsset::find($this->assetId);
                if ($asset) {
                    $asset->update([
                        'analysis_status' => 'failed',
                        'analysis_error' => $e->getMessage(),
                    ]);
                }
            } catch (Exception $updateException) {
                Log::error('Failed to update asset status', [
                    'asset_id' => $this->assetId,
                    'error' => $updateException->getMessage(),
                ]);
            }

            // Re-throw to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Video processing job permanently failed', [
            'asset_id' => $this->assetId,
            'org_id' => $this->orgId,
            'error' => $exception->getMessage(),
        ]);

        try {
            // Set RLS context
            DB::statement("SELECT cmis.init_transaction_context(?)", [$this->orgId]);

            $asset = MediaAsset::find($this->assetId);
            if ($asset) {
                $asset->update([
                    'analysis_status' => 'failed',
                    'analysis_error' => 'Processing failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to update asset status on job failure', [
                'asset_id' => $this->assetId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'video-processing',
            'org:' . $this->orgId,
            'asset:' . $this->assetId,
        ];
    }
}
