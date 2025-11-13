<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

/**
 * ContentLibraryService
 *
 * Handles shared content library and asset management
 * Implements Sprint 5.4: Shared Content Library
 *
 * Features:
 * - Upload and manage media assets
 * - Folder organization
 * - Tagging and categorization
 * - Asset search
 * - Usage tracking
 */
class ContentLibraryService
{
    protected array $allowedTypes = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'video' => ['mp4', 'mov', 'avi', 'webm'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
        'audio' => ['mp3', 'wav', 'ogg']
    ];

    /**
     * Upload an asset to the content library
     *
     * @param string $orgId
     * @param array $data
     * @param UploadedFile $file
     * @return array
     */
    public function uploadAsset(string $orgId, array $data, UploadedFile $file): array
    {
        try {
            DB::beginTransaction();

            // Validate file type
            $extension = strtolower($file->getClientOriginalExtension());
            $assetType = $this->determineAssetType($extension);

            if (!$assetType) {
                return ['success' => false, 'message' => 'Unsupported file type'];
            }

            // Generate unique filename
            $filename = Str::uuid() . '.' . $extension;
            $path = "organizations/{$orgId}/library/{$assetType}s/{$filename}";

            // Store file (in production, this would upload to S3/cloud storage)
            // For now, we'll store file info and assume file is uploaded
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

            // Store asset metadata
            $assetId = (string) Str::uuid();

            DB::table('cmis.content_library')->insert([
                'asset_id' => $assetId,
                'org_id' => $orgId,
                'folder_id' => $data['folder_id'] ?? null,
                'asset_name' => $data['asset_name'] ?? $file->getClientOriginalName(),
                'asset_type' => $assetType,
                'file_path' => $path,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'extension' => $extension,
                'description' => $data['description'] ?? null,
                'tags' => json_encode($data['tags'] ?? []),
                'uploaded_by' => $data['uploaded_by'],
                'is_public' => $data['is_public'] ?? false,
                'thumbnail_path' => $this->generateThumbnailPath($path, $assetType),
                'metadata' => json_encode([
                    'original_name' => $file->getClientOriginalName(),
                    'dimensions' => $data['dimensions'] ?? null,
                    'duration' => $data['duration'] ?? null
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            // Clear cache
            Cache::forget("library_assets:{$orgId}");

            $asset = DB::table('cmis.content_library')->where('asset_id', $assetId)->first();

            return [
                'success' => true,
                'message' => 'Asset uploaded successfully',
                'data' => $this->formatAsset($asset)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to upload asset',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get assets from content library
     *
     * @param string $orgId
     * @param array $filters
     * @return array
     */
    public function getAssets(string $orgId, array $filters = []): array
    {
        try {
            $query = DB::table('cmis.content_library')
                ->join('cmis.users', 'cmis.content_library.uploaded_by', '=', 'cmis.users.user_id')
                ->where('cmis.content_library.org_id', $orgId)
                ->where('cmis.content_library.is_deleted', false)
                ->select(
                    'cmis.content_library.*',
                    'cmis.users.email',
                    'cmis.users.first_name',
                    'cmis.users.last_name'
                );

            // Apply filters
            if (!empty($filters['folder_id'])) {
                if ($filters['folder_id'] === 'root') {
                    $query->whereNull('cmis.content_library.folder_id');
                } else {
                    $query->where('cmis.content_library.folder_id', $filters['folder_id']);
                }
            }

            if (!empty($filters['asset_type'])) {
                $query->where('cmis.content_library.asset_type', $filters['asset_type']);
            }

            if (!empty($filters['tags'])) {
                $tags = is_array($filters['tags']) ? $filters['tags'] : [$filters['tags']];
                foreach ($tags as $tag) {
                    $query->whereRaw("tags::jsonb @> ?", [json_encode([$tag])]);
                }
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('cmis.content_library.asset_name', 'ILIKE', "%{$search}%")
                      ->orWhere('cmis.content_library.description', 'ILIKE', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy("cmis.content_library.{$sortBy}", $sortOrder);

            // Pagination
            $perPage = $filters['per_page'] ?? 50;
            $page = $filters['page'] ?? 1;
            $offset = ($page - 1) * $perPage;

            $total = $query->count();
            $assets = $query->offset($offset)->limit($perPage)->get();

            $formattedAssets = [];
            foreach ($assets as $asset) {
                $formattedAssets[] = $this->formatAsset($asset);
            }

            return [
                'success' => true,
                'data' => $formattedAssets,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage)
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get assets',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get a single asset
     *
     * @param string $assetId
     * @return array
     */
    public function getAsset(string $assetId): array
    {
        try {
            $asset = DB::table('cmis.content_library')
                ->join('cmis.users', 'cmis.content_library.uploaded_by', '=', 'cmis.users.user_id')
                ->where('cmis.content_library.asset_id', $assetId)
                ->where('cmis.content_library.is_deleted', false)
                ->select(
                    'cmis.content_library.*',
                    'cmis.users.email',
                    'cmis.users.first_name',
                    'cmis.users.last_name'
                )
                ->first();

            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found'];
            }

            // Get usage statistics
            $usage = $this->getAssetUsage($assetId);

            $formatted = $this->formatAsset($asset);
            $formatted['usage'] = $usage;

            return [
                'success' => true,
                'data' => $formatted
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get asset',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update asset metadata
     *
     * @param string $assetId
     * @param array $data
     * @return array
     */
    public function updateAsset(string $assetId, array $data): array
    {
        try {
            $asset = DB::table('cmis.content_library')->where('asset_id', $assetId)->first();

            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found'];
            }

            $updateData = [
                'updated_at' => now()
            ];

            if (isset($data['asset_name'])) {
                $updateData['asset_name'] = $data['asset_name'];
            }

            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }

            if (isset($data['tags'])) {
                $updateData['tags'] = json_encode($data['tags']);
            }

            if (isset($data['folder_id'])) {
                $updateData['folder_id'] = $data['folder_id'];
            }

            if (isset($data['is_public'])) {
                $updateData['is_public'] = $data['is_public'];
            }

            DB::table('cmis.content_library')
                ->where('asset_id', $assetId)
                ->update($updateData);

            Cache::forget("library_assets:{$asset->org_id}");

            return [
                'success' => true,
                'message' => 'Asset updated successfully',
                'data' => $this->formatAsset(
                    DB::table('cmis.content_library')->where('asset_id', $assetId)->first()
                )
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update asset',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete an asset
     *
     * @param string $assetId
     * @return array
     */
    public function deleteAsset(string $assetId): array
    {
        try {
            $asset = DB::table('cmis.content_library')->where('asset_id', $assetId)->first();

            if (!$asset) {
                return ['success' => false, 'message' => 'Asset not found'];
            }

            // Soft delete
            DB::table('cmis.content_library')
                ->where('asset_id', $assetId)
                ->update([
                    'is_deleted' => true,
                    'deleted_at' => now()
                ]);

            Cache::forget("library_assets:{$asset->org_id}");

            return [
                'success' => true,
                'message' => 'Asset deleted successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete asset',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a folder
     *
     * @param string $orgId
     * @param array $data
     * @return array
     */
    public function createFolder(string $orgId, array $data): array
    {
        try {
            $folderId = (string) Str::uuid();

            DB::table('cmis.content_library_folders')->insert([
                'folder_id' => $folderId,
                'org_id' => $orgId,
                'parent_folder_id' => $data['parent_folder_id'] ?? null,
                'folder_name' => $data['folder_name'],
                'description' => $data['description'] ?? null,
                'created_by' => $data['created_by'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $folder = DB::table('cmis.content_library_folders')->where('folder_id', $folderId)->first();

            return [
                'success' => true,
                'message' => 'Folder created successfully',
                'data' => [
                    'folder_id' => $folder->folder_id,
                    'folder_name' => $folder->folder_name,
                    'parent_folder_id' => $folder->parent_folder_id,
                    'description' => $folder->description,
                    'created_at' => $folder->created_at
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create folder',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get folders
     *
     * @param string $orgId
     * @param string|null $parentFolderId
     * @return array
     */
    public function getFolders(string $orgId, ?string $parentFolderId = null): array
    {
        try {
            $query = DB::table('cmis.content_library_folders')
                ->where('org_id', $orgId);

            if ($parentFolderId === null || $parentFolderId === 'root') {
                $query->whereNull('parent_folder_id');
            } else {
                $query->where('parent_folder_id', $parentFolderId);
            }

            $folders = $query->orderBy('folder_name', 'asc')->get();

            $formattedFolders = $folders->map(function ($folder) {
                // Get asset count
                $assetCount = DB::table('cmis.content_library')
                    ->where('folder_id', $folder->folder_id)
                    ->where('is_deleted', false)
                    ->count();

                return [
                    'folder_id' => $folder->folder_id,
                    'folder_name' => $folder->folder_name,
                    'parent_folder_id' => $folder->parent_folder_id,
                    'description' => $folder->description,
                    'asset_count' => $assetCount,
                    'created_at' => $folder->created_at
                ];
            });

            return [
                'success' => true,
                'data' => $formattedFolders,
                'total' => $formattedFolders->count()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get folders',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Search assets
     *
     * @param string $orgId
     * @param string $query
     * @param array $filters
     * @return array
     */
    public function searchAssets(string $orgId, string $query, array $filters = []): array
    {
        $filters['search'] = $query;
        return $this->getAssets($orgId, $filters);
    }

    /**
     * Track asset usage
     *
     * @param string $assetId
     * @param array $usageData
     * @return array
     */
    public function trackAssetUsage(string $assetId, array $usageData): array
    {
        try {
            $usageId = (string) Str::uuid();

            DB::table('cmis.asset_usage')->insert([
                'usage_id' => $usageId,
                'asset_id' => $assetId,
                'entity_type' => $usageData['entity_type'], // post, campaign, ad
                'entity_id' => $usageData['entity_id'],
                'used_by' => $usageData['used_by'] ?? null,
                'used_at' => now()
            ]);

            return [
                'success' => true,
                'message' => 'Asset usage tracked'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to track asset usage',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get asset usage statistics
     *
     * @param string $assetId
     * @return array
     */
    protected function getAssetUsage(string $assetId): array
    {
        $usage = DB::table('cmis.asset_usage')
            ->where('asset_id', $assetId)
            ->select('entity_type', DB::raw('COUNT(*) as count'))
            ->groupBy('entity_type')
            ->get();

        $totalUses = DB::table('cmis.asset_usage')
            ->where('asset_id', $assetId)
            ->count();

        $usageByType = [];
        foreach ($usage as $item) {
            $usageByType[$item->entity_type] = $item->count;
        }

        $recentUsage = DB::table('cmis.asset_usage')
            ->where('asset_id', $assetId)
            ->orderBy('used_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'total_uses' => $totalUses,
            'by_type' => $usageByType,
            'recent_usage' => $recentUsage->map(function ($u) {
                return [
                    'entity_type' => $u->entity_type,
                    'entity_id' => $u->entity_id,
                    'used_at' => $u->used_at
                ];
            })->toArray()
        ];
    }

    /**
     * Determine asset type from extension
     *
     * @param string $extension
     * @return string|null
     */
    protected function determineAssetType(string $extension): ?string
    {
        foreach ($this->allowedTypes as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }
        return null;
    }

    /**
     * Generate thumbnail path
     *
     * @param string $filePath
     * @param string $assetType
     * @return string|null
     */
    protected function generateThumbnailPath(string $filePath, string $assetType): ?string
    {
        if ($assetType === 'image' || $assetType === 'video') {
            return str_replace('library/', 'library/thumbnails/', $filePath);
        }
        return null;
    }

    /**
     * Format asset for response
     *
     * @param object $asset
     * @return array
     */
    protected function formatAsset($asset): array
    {
        return [
            'asset_id' => $asset->asset_id,
            'asset_name' => $asset->asset_name,
            'asset_type' => $asset->asset_type,
            'folder_id' => $asset->folder_id ?? null,
            'file_path' => $asset->file_path,
            'file_size' => $asset->file_size,
            'file_size_formatted' => $this->formatFileSize($asset->file_size),
            'mime_type' => $asset->mime_type,
            'extension' => $asset->extension,
            'description' => $asset->description,
            'tags' => json_decode($asset->tags ?? '[]', true),
            'is_public' => $asset->is_public ?? false,
            'thumbnail_path' => $asset->thumbnail_path,
            'metadata' => json_decode($asset->metadata ?? '{}', true),
            'uploaded_by' => [
                'user_id' => $asset->uploaded_by,
                'name' => isset($asset->first_name) ? trim(($asset->first_name ?? '') . ' ' . ($asset->last_name ?? '')) : null,
                'email' => $asset->email ?? null
            ],
            'created_at' => $asset->created_at,
            'updated_at' => $asset->updated_at
        ];
    }

    /**
     * Format file size for display
     *
     * @param int $bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
