<?php

namespace App\Services;

use App\Models\CreativeAsset;
use App\Models\CreativeBrief;
use App\Repositories\Contracts\CreativeRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreativeService
{
    protected CreativeRepositoryInterface $creativeRepo;

    public function __construct(CreativeRepositoryInterface $creativeRepo)
    {
        $this->creativeRepo = $creativeRepo;
    }
    /**
     * Upload and process creative asset
     */
    public function uploadAsset(array $data, $file): CreativeAsset
    {
        try {
            DB::beginTransaction();

            // Store file
            $path = $file->store('assets/' . $data['asset_type'], 'public');
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

            // Create asset record
            $asset = CreativeAsset::create([
                'asset_id' => \Illuminate\Support\Str::uuid(),
                'asset_name' => $data['asset_name'],
                'asset_type' => $data['asset_type'],
                'file_path' => $path,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'org_id' => $data['org_id'],
                'status' => 'pending_review',
                'metadata' => $data['metadata'] ?? [],
            ]);

            // Extract metadata based on file type
            if (str_starts_with($mimeType, 'image/')) {
                $this->extractImageMetadata($asset, Storage::disk('public')->path($path));
            } elseif (str_starts_with($mimeType, 'video/')) {
                $this->extractVideoMetadata($asset, Storage::disk('public')->path($path));
            }

            DB::commit();

            Log::info('Creative asset uploaded', [
                'asset_id' => $asset->asset_id,
                'type' => $asset->asset_type,
            ]);

            return $asset;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to upload creative asset', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract image metadata
     */
    protected function extractImageMetadata(CreativeAsset $asset, string $filePath): void
    {
        try {
            if (function_exists('getimagesize')) {
                $imageInfo = getimagesize($filePath);
                if ($imageInfo) {
                    $metadata = $asset->metadata ?? [];
                    $metadata['width'] = $imageInfo[0];
                    $metadata['height'] = $imageInfo[1];
                    $metadata['type'] = $imageInfo['mime'];

                    $asset->update(['metadata' => $metadata]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to extract image metadata', [
                'asset_id' => $asset->asset_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract video metadata
     */
    protected function extractVideoMetadata(CreativeAsset $asset, string $filePath): void
    {
        try {
            // You would use FFmpeg or similar tool to extract video metadata
            // For now, just log
            Log::info('Video metadata extraction requested', [
                'asset_id' => $asset->asset_id,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to extract video metadata', [
                'asset_id' => $asset->asset_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate asset variations
     */
    public function generateVariations(CreativeAsset $asset, array $sizes): array
    {
        try {
            $variations = [];

            foreach ($sizes as $size) {
                // Generate variation using image processing library
                // For now, just create placeholder records
                $variations[] = [
                    'size' => $size,
                    'width' => $size['width'] ?? null,
                    'height' => $size['height'] ?? null,
                    'url' => $asset->file_path, // Would be actual variation path
                ];
            }

            Log::info('Asset variations generated', [
                'asset_id' => $asset->asset_id,
                'count' => count($variations),
            ]);

            return $variations;
        } catch (\Exception $e) {
            Log::error('Failed to generate asset variations', [
                'asset_id' => $asset->asset_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Approve asset
     */
    public function approveAsset(string $assetId, string $approvedBy): bool
    {
        try {
            $asset = CreativeAsset::findOrFail($assetId);

            $asset->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            Log::info('Asset approved', [
                'asset_id' => $assetId,
                'approved_by' => $approvedBy,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to approve asset', [
                'asset_id' => $assetId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Reject asset
     */
    public function rejectAsset(string $assetId, string $reason): bool
    {
        try {
            $asset = CreativeAsset::findOrFail($assetId);

            $asset->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'rejected_at' => now(),
            ]);

            Log::info('Asset rejected', [
                'asset_id' => $assetId,
                'reason' => $reason,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to reject asset', [
                'asset_id' => $assetId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create creative brief
     */
    public function createBrief(array $data): CreativeBrief
    {
        try {
            $brief = CreativeBrief::create([
                'brief_id' => \Illuminate\Support\Str::uuid(),
                'campaign_id' => $data['campaign_id'],
                'org_id' => $data['org_id'],
                'brief_content' => $data['brief_content'],
                'objectives' => $data['objectives'] ?? [],
                'target_audience' => $data['target_audience'] ?? [],
                'key_messages' => $data['key_messages'] ?? [],
                'deliverables' => $data['deliverables'] ?? [],
                'deadline' => $data['deadline'] ?? null,
                'status' => 'draft',
            ]);

            Log::info('Creative brief created', [
                'brief_id' => $brief->brief_id,
                'campaign_id' => $brief->campaign_id,
            ]);

            return $brief;
        } catch (\Exception $e) {
            Log::error('Failed to create creative brief', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get asset analytics
     */
    public function getAssetAnalytics(string $assetId): array
    {
        try {
            $asset = CreativeAsset::findOrFail($assetId);

            // Get usage statistics
            $usageCount = DB::table('cmis.campaign_assets')
                ->where('asset_id', $assetId)
                ->count();

            $performanceData = DB::table('cmis.asset_performance')
                ->where('asset_id', $assetId)
                ->selectRaw('
                    AVG(engagement_rate) as avg_engagement,
                    AVG(click_through_rate) as avg_ctr,
                    SUM(impressions) as total_impressions,
                    SUM(clicks) as total_clicks
                ')
                ->first();

            return [
                'asset' => $asset,
                'usage_count' => $usageCount,
                'performance' => [
                    'avg_engagement' => $performanceData->avg_engagement ?? 0,
                    'avg_ctr' => $performanceData->avg_ctr ?? 0,
                    'total_impressions' => $performanceData->total_impressions ?? 0,
                    'total_clicks' => $performanceData->total_clicks ?? 0,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get asset analytics', [
                'asset_id' => $assetId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Delete asset
     */
    public function deleteAsset(string $assetId): bool
    {
        try {
            $asset = CreativeAsset::findOrFail($assetId);

            // Delete file from storage
            if ($asset->file_path) {
                Storage::disk('public')->delete($asset->file_path);
            }

            // Delete record
            $asset->delete();

            Log::info('Asset deleted', [
                'asset_id' => $assetId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete asset', [
                'asset_id' => $assetId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Search assets
     */
    public function searchAssets(array $criteria): array
    {
        try {
            $query = CreativeAsset::query();

            if (isset($criteria['asset_type'])) {
                $query->where('asset_type', $criteria['asset_type']);
            }

            if (isset($criteria['status'])) {
                $query->where('status', $criteria['status']);
            }

            if (isset($criteria['org_id'])) {
                $query->where('org_id', $criteria['org_id']);
            }

            if (isset($criteria['search'])) {
                $query->where('asset_name', 'like', '%' . $criteria['search'] . '%');
            }

            $assets = $query->orderBy('created_at', 'desc')
                ->limit($criteria['limit'] ?? 20)
                ->get();

            return $assets->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to search assets', [
                'criteria' => $criteria,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
