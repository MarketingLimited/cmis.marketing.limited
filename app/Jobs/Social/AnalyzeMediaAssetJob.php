<?php

namespace App\Jobs\Social;

use App\Models\Social\MediaAsset;
use App\Services\Social\VisualAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Analyze Media Asset Job
 *
 * Asynchronously analyzes a media asset using Google Gemini Vision.
 */
class AnalyzeMediaAssetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180; // 3 minutes
    public $tries = 2;

    private string $assetId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $assetId)
    {
        $this->assetId = $assetId;
    }

    /**
     * Execute the job.
     */
    public function handle(VisualAnalysisService $service): void
    {
        try {
            $asset = MediaAsset::findOrFail($this->assetId);

            // Set org context for RLS
            \DB::statement("SET app.current_org_id = '{$asset->org_id}'");

            Log::info('Analyzing media asset', [
                'asset_id' => $this->assetId,
                'media_type' => $asset->media_type,
            ]);

            $result = $service->analyzeMediaAsset($asset);

            Log::info('Media asset analysis completed', [
                'asset_id' => $this->assetId,
                'extracted_text' => $result['extracted_text'] ? 'yes' : 'no',
                'detected_objects' => count($result['detected_objects'] ?? []),
                'color_palette' => isset($result['color_palette']) ? 'yes' : 'no',
            ]);

        } catch (\Exception $e) {
            Log::error('Media asset analysis job failed', [
                'asset_id' => $this->assetId,
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
        Log::error('Media asset analysis job failed permanently', [
            'asset_id' => $this->assetId,
            'error' => $exception->getMessage(),
        ]);

        // Mark asset analysis as failed
        try {
            $asset = MediaAsset::find($this->assetId);
            if ($asset) {
                $asset->update([
                    'analysis_status' => 'failed',
                    'analysis_error' => $exception->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            // Ignore
        }
    }
}
