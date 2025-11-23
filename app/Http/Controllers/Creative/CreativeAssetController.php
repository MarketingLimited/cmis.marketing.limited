<?php

namespace App\Http\Controllers\Creative;

use App\Http\Controllers\Controller;
use App\Models\CreativeAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\ApiResponse;

class CreativeAssetController extends Controller
{
    use ApiResponse;

    /**
     * Constructor - Apply authentication middleware
     */
    public function __construct()
    {
        // Apply authentication to all creative asset operations
        // Assets include file uploads and sensitive creative content
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request, string $orgId)
    {
        $this->authorize('viewAny', CreativeAsset::class);

        try {
            $perPage = $request->input('per_page', 20);
            $status = $request->input('status');
            $campaignId = $request->input('campaign_id');

            $query = CreativeAsset::where('org_id', $orgId);

            if ($status) {
                $query->where('status', $status);
            }

            if ($campaignId) {
                $query->where('campaign_id', $campaignId);
            }

            $assets = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->paginated($assets, 'Assets retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch assets: ' . $e->getMessage());
        }
    }

    public function store(Request $request, string $orgId)
    {
        $this->authorize('create', CreativeAsset::class);

        $validator = Validator::make($request->all(), [
            'campaign_id' => 'nullable|uuid|exists:cmis.campaigns,campaign_id',
            'channel_id' => 'required|integer',
            'format_id' => 'nullable|integer',
            'variation_tag' => 'nullable|string',
            'copy_block' => 'nullable|string',
            'strategy' => 'nullable|array',
            'art_direction' => 'nullable|array',
            'status' => 'nullable|in:draft,pending_review,approved,rejected,archived',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $asset = CreativeAsset::create([
                'org_id' => $orgId,
                'campaign_id' => $request->campaign_id,
                'channel_id' => $request->channel_id,
                'format_id' => $request->format_id,
                'variation_tag' => $request->variation_tag,
                'copy_block' => $request->copy_block,
                'strategy' => $request->strategy,
                'art_direction' => $request->art_direction,
                'status' => $request->status ?? 'draft',
            ]);

            return $this->created($asset->fresh(), 'Asset created successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to create asset: ' . $e->getMessage());
        }
    }

    public function show(Request $request, string $orgId, string $assetId)
    {
        try {
            $asset = CreativeAsset::where('org_id', $orgId)->findOrFail($assetId);
            $this->authorize('view', $asset);
            return $this->success($asset, 'Asset retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFound('Asset not found');
        }
    }

    public function update(Request $request, string $orgId, string $assetId)
    {
        $asset = CreativeAsset::where('org_id', $orgId)->findOrFail($assetId);
        $this->authorize('update', $asset);

        $validator = Validator::make($request->all(), [
            'campaign_id' => 'sometimes|uuid|exists:cmis.campaigns,campaign_id',
            'channel_id' => 'sometimes|integer',
            'format_id' => 'sometimes|integer',
            'variation_tag' => 'sometimes|string',
            'copy_block' => 'sometimes|string',
            'strategy' => 'sometimes|array',
            'art_direction' => 'sometimes|array',
            'final_copy' => 'sometimes|array',
            'status' => 'sometimes|in:draft,pending_review,approved,rejected,archived',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $asset->update($request->only([
                'campaign_id', 'channel_id', 'format_id', 'variation_tag',
                'copy_block', 'strategy', 'art_direction', 'final_copy', 'status'
            ]));
            return $this->success($asset->fresh(), 'Asset updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Asset not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, string $orgId, string $assetId)
    {
        try {
            $asset = CreativeAsset::where('org_id', $orgId)->findOrFail($assetId);
            $this->authorize('delete', $asset);
            $asset->delete();
            return $this->deleted('Asset deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete asset');
        }
    }
}
