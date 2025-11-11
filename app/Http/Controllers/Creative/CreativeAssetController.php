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
            $type = $request->input('type');

            $query = CreativeAsset::where('org_id', $orgId);

            if ($type) {
                $query->where('asset_type', $type);
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
            'asset_type' => 'required|string',
            'format' => 'required|string',
            'width_px' => 'nullable|integer',
            'height_px' => 'nullable|integer',
            'url' => 'nullable|url',
            'local_path' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        try {
            $asset = CreativeAsset::create(array_merge(
                $request->all(),
                ['org_id' => $orgId]
            ));

            return response()->json(['message' => 'Asset created', 'asset' => $asset], 201);

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
        try {
            $asset = CreativeAsset::where('org_id', $orgId)->findOrFail($assetId);
            $asset->update($request->all());
            return response()->json(['message' => 'Asset updated', 'asset' => $asset]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update'], 500);
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
