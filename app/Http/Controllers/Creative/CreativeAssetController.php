<?php

namespace App\Http\Controllers\Creative;

use App\Http\Controllers\Controller;
use App\Models\CreativeAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CreativeAssetController extends Controller
{
    public function index(Request $request, string $orgId)
    {
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

            return response()->json($assets);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch assets', 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request, string $orgId)
    {
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
            return response()->json(['error' => 'Validation Error', 'errors' => $validator->errors()], 422);
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

            return response()->json(['message' => 'Asset created', 'asset' => $asset->fresh()], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create asset', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, string $orgId, string $assetId)
    {
        try {
            $asset = CreativeAsset::where('org_id', $orgId)->findOrFail($assetId);
            return response()->json(['asset' => $asset]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Asset not found'], 404);
        }
    }

    public function update(Request $request, string $orgId, string $assetId)
    {
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
            return response()->json(['error' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        try {
            $asset = CreativeAsset::where('org_id', $orgId)->findOrFail($assetId);
            $asset->update($request->only([
                'campaign_id', 'channel_id', 'format_id', 'variation_tag',
                'copy_block', 'strategy', 'art_direction', 'final_copy', 'status'
            ]));
            return response()->json(['message' => 'Asset updated', 'asset' => $asset->fresh()]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Asset not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, string $orgId, string $assetId)
    {
        try {
            $asset = CreativeAsset::where('org_id', $orgId)->findOrFail($assetId);
            $asset->delete();
            return response()->json(['message' => 'Asset deleted']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete'], 500);
        }
    }
}
