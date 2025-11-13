<?php

namespace App\Http\Controllers;

use App\Services\ContentLibraryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * ContentLibraryController
 *
 * Handles shared content library
 * Implements Sprint 5.4: Shared Content Library
 */
class ContentLibraryController extends Controller
{
    protected ContentLibraryService $libraryService;

    public function __construct(ContentLibraryService $libraryService)
    {
        $this->libraryService = $libraryService;
    }

    /**
     * Upload asset
     * POST /api/orgs/{org_id}/content-library/upload
     */
    public function upload(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:102400', // 100MB max
            'asset_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'folder_id' => 'nullable|uuid',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_public' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $userId = $request->user()->user_id ?? null;
            if (!$userId) {
                return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
            }

            $result = $this->libraryService->uploadAsset($orgId, [
                'asset_name' => $request->input('asset_name'),
                'description' => $request->input('description'),
                'folder_id' => $request->input('folder_id'),
                'tags' => $request->input('tags', []),
                'is_public' => $request->input('is_public', false),
                'uploaded_by' => $userId
            ], $request->file('file'));

            return response()->json($result, $result['success'] ? 201 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Upload failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * List assets
     * GET /api/orgs/{org_id}/content-library?folder_id=&asset_type=image&tags[]=marketing
     */
    public function list(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'folder_id' => 'nullable|string',
            'asset_type' => 'nullable|in:image,video,document,audio',
            'tags' => 'nullable|array',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:created_at,asset_name,file_size',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->libraryService->getAssets($orgId, $request->all());
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to list assets', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get single asset
     * GET /api/orgs/{org_id}/content-library/{asset_id}
     */
    public function show(string $orgId, string $assetId): JsonResponse
    {
        try {
            $result = $this->libraryService->getAsset($assetId);
            return response()->json($result, $result['success'] ? 200 : 404);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to get asset', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update asset
     * PUT /api/orgs/{org_id}/content-library/{asset_id}
     */
    public function update(string $orgId, string $assetId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'asset_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'folder_id' => 'nullable|uuid',
            'tags' => 'nullable|array',
            'is_public' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->libraryService->updateAsset($assetId, $request->all());
            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update asset', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete asset
     * DELETE /api/orgs/{org_id}/content-library/{asset_id}
     */
    public function delete(string $orgId, string $assetId): JsonResponse
    {
        try {
            $result = $this->libraryService->deleteAsset($assetId);
            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete asset', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create folder
     * POST /api/orgs/{org_id}/content-library/folders
     */
    public function createFolder(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'folder_name' => 'required|string|max:255',
            'parent_folder_id' => 'nullable|uuid',
            'description' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $userId = $request->user()->user_id ?? null;
            if (!$userId) {
                return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
            }

            $result = $this->libraryService->createFolder($orgId, [
                'folder_name' => $request->input('folder_name'),
                'parent_folder_id' => $request->input('parent_folder_id'),
                'description' => $request->input('description'),
                'created_by' => $userId
            ]);

            return response()->json($result, $result['success'] ? 201 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create folder', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * List folders
     * GET /api/orgs/{org_id}/content-library/folders?parent_folder_id=root
     */
    public function listFolders(string $orgId, Request $request): JsonResponse
    {
        try {
            $parentFolderId = $request->input('parent_folder_id');
            $result = $this->libraryService->getFolders($orgId, $parentFolderId);
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to list folders', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Search assets
     * GET /api/orgs/{org_id}/content-library/search?q=logo&asset_type=image
     */
    public function search(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->libraryService->searchAssets($orgId, $request->input('q'), $request->except('q'));
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Search failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Track asset usage
     * POST /api/orgs/{org_id}/content-library/{asset_id}/track-usage
     */
    public function trackUsage(string $orgId, string $assetId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|in:post,campaign,ad',
            'entity_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->libraryService->trackAssetUsage($assetId, [
                'entity_type' => $request->input('entity_type'),
                'entity_id' => $request->input('entity_id'),
                'used_by' => $request->user()->user_id ?? null
            ]);

            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to track usage', 'error' => $e->getMessage()], 500);
        }
    }
}
