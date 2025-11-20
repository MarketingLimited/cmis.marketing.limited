<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\Asset\ImageAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Image Asset Controller
 *
 * Handles HTTP requests for image asset management
 * Note: Stub implementation
 */
class ImageAssetController extends Controller
{
    /**
     * Display a listing of image assets with filtering and pagination
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        Log::info('ImageAssetController::index called (stub)', [
            'filters' => $request->all(),
        ]);

        return response()->json([
            'data' => [],
            'pagination' => ['total' => 0, 'per_page' => 15, 'current_page' => 1],
            'stub' => true
        ], 200);
    }

    /**
     * Return form/metadata for creating a new image asset
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        Log::info('ImageAssetController::create called (stub)');

        return response()->json([
            'form_fields' => [],
            'stub' => true
        ], 200);
    }

    /**
     * Store a newly created image asset in database
     *
     * @param Request $request HTTP request with image asset data and file upload
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('ImageAssetController::store called (stub)', [
            'data' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'id' => 'image_asset_stub_' . uniqid(),
            'stub' => true
        ], 201);
    }

    /**
     * Display the specified image asset by ID
     *
     * @param Request $request HTTP request
     * @param string $asset_id Image Asset ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $asset_id)
    {
        Log::info('ImageAssetController::show called (stub)', [
            'asset_id' => $asset_id,
        ]);

        return response()->json([
            'data' => ['id' => $asset_id],
            'stub' => true
        ], 200);
    }

    /**
     * Return form/metadata for editing the specified image asset
     *
     * @param Request $request HTTP request
     * @param string $asset_id Image Asset ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request, $asset_id)
    {
        Log::info('ImageAssetController::edit called (stub)', [
            'asset_id' => $asset_id,
        ]);

        return response()->json([
            'form_fields' => ['id' => $asset_id],
            'stub' => true
        ], 200);
    }

    /**
     * Update the specified image asset in database
     *
     * @param Request $request HTTP request with image asset data
     * @param string $asset_id Image Asset ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $asset_id)
    {
        Log::info('ImageAssetController::update called (stub)', [
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
     * Remove the specified image asset from database
     *
     * @param Request $request HTTP request
     * @param string $asset_id Image Asset ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $asset_id)
    {
        Log::info('ImageAssetController::destroy called (stub)', [
            'asset_id' => $asset_id,
        ]);

        return response()->json([
            'success' => true,
            'stub' => true
        ], 200);
    }
}
