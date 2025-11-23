<?php

namespace App\Http\Controllers;

use App\Models\CreativeAsset;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

/**
 * Controller for managing creative assets
 * Handles asset uploads, metadata management, and downloads
 */
class AssetController extends Controller
{
    use ApiResponse;

    /**
     * Constructor - Apply authentication middleware
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List creative assets
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return response()->json(['error' => 'No active organization found'], 404);
            }

            $query = CreativeAsset::where('org_id', $orgId);

            // Filter by campaign
            if ($request->has('campaign_id')) {
                $query->where('campaign_id', $request->input('campaign_id'));
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            // Filter by type (format)
            if ($request->has('format_id')) {
                $query->where('format_id', $request->input('format_id'));
            }

            // Pagination
            $perPage = $request->input('per_page', 20);
            $assets = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'data' => $assets->items(),
                'total' => $assets->total(),
                'per_page' => $assets->perPage(),
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to list assets: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload new asset
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'campaign_id' => 'nullable|string',
                'channel_id' => 'nullable|integer',
                'format_id' => 'nullable|integer',
                'file' => 'nullable|file|max:10240', // 10MB max
                'final_copy' => 'nullable|array',
                'art_direction' => 'nullable|array',
                'status' => 'nullable|string|in:draft,pending,approved,rejected',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $orgId = $this->resolveOrgId($request);
            $user = $request->user();

            if (!$orgId) {
                return response()->json(['error' => 'No active organization found'], 404);
            }

            // Initialize RLS context
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $user->user_id,
                $orgId
            ]);

            $data = [
                'org_id' => $orgId,
                'campaign_id' => $request->input('campaign_id'),
                'channel_id' => $request->input('channel_id'),
                'format_id' => $request->input('format_id'),
                'final_copy' => $request->input('final_copy'),
                'art_direction' => $request->input('art_direction'),
                'status' => $request->input('status', 'draft'),
            ];

            // Handle file upload if present
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('assets/' . $orgId, 'public');
                $data['provider'] = $path;
            }

            $asset = CreativeAsset::create($data);

            return response()->json([
                'data' => $asset,
                'message' => 'Asset created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error("Failed to create asset: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show asset details
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $id, Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return response()->json(['error' => 'No active organization found'], 404);
            }

            $asset = CreativeAsset::where('asset_id', $id)
                ->where('org_id', $orgId)
                ->firstOrFail();

            return response()->json(['data' => $asset]);
        } catch (\Exception $e) {
            Log::error("Failed to get asset: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update asset metadata
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);
            $user = $request->user();

            if (!$orgId) {
                return response()->json(['error' => 'No active organization found'], 404);
            }

            // Initialize RLS context
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $user->user_id,
                $orgId
            ]);

            $asset = CreativeAsset::where('asset_id', $id)
                ->where('org_id', $orgId)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'final_copy' => 'sometimes|array',
                'art_direction' => 'sometimes|array',
                'status' => 'sometimes|string|in:draft,pending,approved,rejected',
                'variation_tag' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $asset->update($request->only([
                'final_copy',
                'art_direction',
                'status',
                'variation_tag',
            ]));

            return $this->success($asset, 'Asset updated successfully');
        } catch (\Exception $e) {
            Log::error("Failed to update asset: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete asset
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);
            $user = $request->user();

            if (!$orgId) {
                return response()->json(['error' => 'No active organization found'], 404);
            }

            // Initialize RLS context
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $user->user_id,
                $orgId
            ]);

            $asset = CreativeAsset::where('asset_id', $id)
                ->where('org_id', $orgId)
                ->firstOrFail();

            // Soft delete
            $asset->deleted_by = $user->user_id;
            $asset->save();
            $asset->delete();

            return response()->json([
                'message' => 'Asset deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to delete asset: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download asset file
     *
     * @param string $id
     * @param Request $request
     * @return mixed
     */
    public function download(string $id, Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return response()->json(['error' => 'No active organization found'], 404);
            }

            $asset = CreativeAsset::where('asset_id', $id)
                ->where('org_id', $orgId)
                ->firstOrFail();

            if (!$asset->provider) {
                return response()->json([
                    'error' => 'No file associated with this asset'
                ], 404);
            }

            // Check if file exists
            if (!Storage::disk('public')->exists($asset->provider)) {
                return response()->json([
                    'error' => 'File not found'
                ], 404);
            }

            return Storage::disk('public')->download($asset->provider);
        } catch (\Exception $e) {
            Log::error("Failed to download asset: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Resolve organization ID from request
     *
     * @param Request $request
     * @return string|null
     */
    private function resolveOrgId(Request $request): ?string
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        // Try to get from route parameter first
        if ($request->route('org_id')) {
            return $request->route('org_id');
        }

        // Fall back to user's active org
        if ($user->active_org_id) {
            return $user->active_org_id;
        }

        // Query the user_orgs pivot table for an active org
        $activeOrg = DB::table('cmis.user_orgs')
            ->where('user_id', $user->user_id)
            ->where('is_active', true)
            ->first();

        return $activeOrg?->org_id;
    }
}
