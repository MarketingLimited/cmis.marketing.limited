<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Social\MediaAsset;
use App\Services\Media\VideoProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * Video Processing Controller
 *
 * Handles video processing status polling and thumbnail updates
 * for social media post creation.
 */
class VideoProcessingController extends Controller
{
    use ApiResponse;

    /**
     * Get processing status for one or more media assets
     *
     * @param Request $request
     * @param string $org Organization ID
     * @return JsonResponse
     */
    public function status(Request $request, string $org): JsonResponse
    {
        $request->validate([
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'required|uuid',
        ]);

        try {
            // Set RLS context
            DB::statement("SELECT cmis.init_transaction_context(?)", [$org]);

            $assetIds = $request->input('asset_ids');
            $statuses = [];
            $allComplete = true;

            $assets = MediaAsset::whereIn('asset_id', $assetIds)->get();

            foreach ($assets as $asset) {
                $metadata = $asset->metadata ?? [];

                $statuses[$asset->asset_id] = [
                    'status' => $asset->analysis_status ?? 'pending',
                    'error' => $asset->analysis_error,
                    'thumbnail_url' => $metadata['thumbnail_url'] ?? null,
                    'thumbnail_time' => $metadata['thumbnail_time'] ?? null,
                    'processed_url' => $metadata['processed_url'] ?? null,
                    'width' => $asset->width,
                    'height' => $asset->height,
                    'duration' => $asset->duration_seconds,
                ];

                if ($asset->analysis_status !== 'completed' && $asset->analysis_status !== 'failed') {
                    $allComplete = false;
                }
            }

            // Handle missing assets
            foreach ($assetIds as $assetId) {
                if (!isset($statuses[$assetId])) {
                    $statuses[$assetId] = [
                        'status' => 'not_found',
                        'error' => 'Asset not found',
                    ];
                }
            }

            return $this->success([
                'statuses' => $statuses,
                'all_complete' => $allComplete,
            ], 'Processing status retrieved');

        } catch (Exception $e) {
            Log::error('Failed to get processing status', [
                'org_id' => $org,
                'asset_ids' => $request->input('asset_ids'),
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Failed to retrieve processing status');
        }
    }

    /**
     * Update thumbnail by extracting a frame at a specific timestamp
     *
     * @param Request $request
     * @param string $org Organization ID
     * @return JsonResponse
     */
    public function updateThumbnail(Request $request, string $org): JsonResponse
    {
        $request->validate([
            'asset_id' => 'required|uuid',
            'timestamp' => 'required|numeric|min:0',
        ]);

        try {
            // Set RLS context
            DB::statement("SELECT cmis.init_transaction_context(?)", [$org]);

            $assetId = $request->input('asset_id');
            $timestamp = (float) $request->input('timestamp');

            $asset = MediaAsset::findOrFail($assetId);

            // Ensure asset is processed
            if ($asset->analysis_status !== 'completed') {
                return $this->error('Video is not yet processed', 400);
            }

            $metadata = $asset->metadata ?? [];

            // Ensure we have the processed video path
            $processedPath = $metadata['processed_path'] ?? null;
            if (!$processedPath) {
                return $this->error('Processed video not found', 400);
            }

            // Validate timestamp against video duration
            $duration = $asset->duration_seconds ?? 0;
            if ($timestamp >= $duration) {
                return $this->error('Timestamp exceeds video duration', 400);
            }

            // Get full path to processed video
            $processedFullPath = Storage::disk('public')->path($processedPath);

            if (!file_exists($processedFullPath)) {
                return $this->error('Processed video file not found', 404);
            }

            // Delete old thumbnail if exists
            $oldThumbnailPath = $metadata['thumbnail_path'] ?? null;
            if ($oldThumbnailPath && Storage::disk('public')->exists($oldThumbnailPath)) {
                Storage::disk('public')->delete($oldThumbnailPath);
            }

            // Extract new thumbnail
            $processingService = app(VideoProcessingService::class);
            $result = $processingService->extractThumbnailAtTimestamp(
                $processedFullPath,
                $timestamp,
                $org
            );

            // Update asset metadata with new thumbnail
            $asset->update([
                'metadata' => array_merge($metadata, [
                    'thumbnail_url' => $result['thumbnail_url'],
                    'thumbnail_path' => $result['thumbnail_path'],
                    'thumbnail_time' => $result['thumbnail_time'],
                    'thumbnail_updated_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::info('Thumbnail updated successfully', [
                'asset_id' => $assetId,
                'org_id' => $org,
                'timestamp' => $timestamp,
            ]);

            return $this->success([
                'thumbnail_url' => $result['thumbnail_url'],
                'thumbnail_time' => $result['thumbnail_time'],
            ], 'Thumbnail updated successfully');

        } catch (Exception $e) {
            Log::error('Failed to update thumbnail', [
                'org_id' => $org,
                'asset_id' => $request->input('asset_id'),
                'timestamp' => $request->input('timestamp'),
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Failed to update thumbnail: ' . $e->getMessage());
        }
    }

    /**
     * Get video frames for thumbnail selection UI
     *
     * Extracts multiple frames at regular intervals for user to select
     *
     * @param Request $request
     * @param string $org Organization ID
     * @return JsonResponse
     */
    public function getFrames(Request $request, string $org): JsonResponse
    {
        $request->validate([
            'asset_id' => 'required|uuid',
            'count' => 'nullable|integer|min:3|max:12',
        ]);

        try {
            // Set RLS context
            DB::statement("SELECT cmis.init_transaction_context(?)", [$org]);

            $assetId = $request->input('asset_id');
            $frameCount = $request->input('count', 6);

            $asset = MediaAsset::findOrFail($assetId);

            if ($asset->analysis_status !== 'completed') {
                return $this->error('Video is not yet processed', 400);
            }

            $metadata = $asset->metadata ?? [];
            $processedPath = $metadata['processed_path'] ?? null;

            if (!$processedPath) {
                return $this->error('Processed video not found', 400);
            }

            $processedFullPath = Storage::disk('public')->path($processedPath);
            $duration = $asset->duration_seconds ?? 0;

            if ($duration <= 0) {
                return $this->error('Invalid video duration', 400);
            }

            // Calculate timestamps for frames
            $frames = [];
            $interval = $duration / ($frameCount + 1);

            $processingService = app(VideoProcessingService::class);

            for ($i = 1; $i <= $frameCount; $i++) {
                $timestamp = $interval * $i;

                try {
                    $result = $processingService->extractThumbnailAtTimestamp(
                        $processedFullPath,
                        $timestamp,
                        $org
                    );

                    $frames[] = [
                        'timestamp' => round($timestamp, 2),
                        'thumbnail_url' => $result['thumbnail_url'],
                    ];
                } catch (Exception $e) {
                    Log::warning('Failed to extract frame', [
                        'asset_id' => $assetId,
                        'timestamp' => $timestamp,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $this->success([
                'frames' => $frames,
                'duration' => $duration,
                'current_thumbnail_time' => $metadata['thumbnail_time'] ?? 1.0,
            ], 'Frames extracted successfully');

        } catch (Exception $e) {
            Log::error('Failed to extract frames', [
                'org_id' => $org,
                'asset_id' => $request->input('asset_id'),
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Failed to extract frames');
        }
    }
}
