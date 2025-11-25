<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Asset\VideoAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Video Asset Controller
 *
 * Handles HTTP requests for video asset management
 * Note: Stub implementation
 */
class VideoAssetController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of video assets with filtering and pagination
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        Log::info('VideoAssetController::index called (stub)', [
            'filters' => $request->all(),
        ]);

        return response()->json([
            'data' => [],
            'pagination' => ['total' => 0, 'per_page' => 15, 'current_page' => 1],
            'stub' => true
        ], 200);
    }

    /**
     * Return form/metadata for creating a new video asset
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        Log::info('VideoAssetController::create called (stub)');

        return response()->json([
            'form_fields' => [],
            'stub' => true
        ], 200);
    }

    /**
     * Store a newly created video asset in database
     *
     * @param Request $request HTTP request with video asset data and file upload
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('VideoAssetController::store called (stub)', [
            'data' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'id' => 'video_asset_stub_' . uniqid(),
            'stub' => true
        ], 201);
    }

    /**
     * Display the specified video asset by ID
     *
     * @param Request $request HTTP request
     * @param string $asset_id Video Asset ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $asset_id)
    {
        Log::info('VideoAssetController::show called (stub)', [
            'asset_id' => $asset_id,
        ]);

        return response()->json([
            'data' => ['id' => $asset_id],
            'stub' => true
        ], 200);
    }

    /**
     * Return form/metadata for editing the specified video asset
     *
     * @param Request $request HTTP request
     * @param string $asset_id Video Asset ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request, $asset_id)
    {
        Log::info('VideoAssetController::edit called (stub)', [
            'asset_id' => $asset_id,
        ]);

        return response()->json([
            'form_fields' => ['id' => $asset_id],
            'stub' => true
        ], 200);
    }

    /**
     * Update the specified video asset in database
     *
     * @param Request $request HTTP request with video asset data
     * @param string $asset_id Video Asset ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $asset_id)
    {
        Log::info('VideoAssetController::update called (stub)', [
            'asset_id' => $asset_id,
            'data' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'id' => $asset_id,
            'stub' => true
        ], 200);
    }

    /**
     * Remove the specified video asset from database
     *
     * @param Request $request HTTP request
     * @param string $asset_id Video Asset ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $asset_id)
    {
        Log::info('VideoAssetController::destroy called (stub)', [
            'asset_id' => $asset_id,
        ]);

        return response()->json([
            'success' => true,
            'stub' => true
        ], 200);
    }
}
