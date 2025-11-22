<?php

namespace App\Http\Controllers\AdPlatform;

use App\Http\Controllers\Controller;
use App\Models\AdPlatform\AdAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Ad Audience Controller
 *
 * Handles HTTP requests for ad audience management
 * Note: Stub implementation
 */
class AdAudienceController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of ad audiences with filtering and pagination
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        Log::info('AdAudienceController::index called (stub)', [
            'filters' => $request->all(),
        ]);

        return response()->json([
            'data' => [],
            'pagination' => ['total' => 0, 'per_page' => 15, 'current_page' => 1],
            'stub' => true
        ], 200);
    }

    /**
     * Return form/metadata for creating a new ad audience
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        Log::info('AdAudienceController::create called (stub)');

        return response()->json([
            'form_fields' => [],
            'stub' => true
        ], 200);
    }

    /**
     * Store a newly created ad audience in database
     *
     * @param Request $request HTTP request with ad audience data
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('AdAudienceController::store called (stub)', [
            'data' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'id' => 'ad_audience_stub_' . uniqid(),
            'stub' => true
        ], 201);
    }

    /**
     * Display the specified ad audience by ID
     *
     * @param Request $request HTTP request
     * @param string $audience_id Ad Audience ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $audience_id)
    {
        Log::info('AdAudienceController::show called (stub)', [
            'audience_id' => $audience_id,
        ]);

        return response()->json([
            'data' => ['id' => $audience_id],
            'stub' => true
        ], 200);
    }

    /**
     * Return form/metadata for editing the specified ad audience
     *
     * @param Request $request HTTP request
     * @param string $audience_id Ad Audience ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request, $audience_id)
    {
        Log::info('AdAudienceController::edit called (stub)', [
            'audience_id' => $audience_id,
        ]);

        return response()->json([
            'form_fields' => ['id' => $audience_id],
            'stub' => true
        ], 200);
    }

    /**
     * Update the specified ad audience in database
     *
     * @param Request $request HTTP request with ad audience data
     * @param string $audience_id Ad Audience ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $audience_id)
    {
        Log::info('AdAudienceController::update called (stub)', [
            'audience_id' => $audience_id,
            'data' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'id' => $audience_id,
            'stub' => true
        ], 200);
    }

    /**
     * Remove the specified ad audience from database
     *
     * @param Request $request HTTP request
     * @param string $audience_id Ad Audience ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $audience_id)
    {
        Log::info('AdAudienceController::destroy called (stub)', [
            'audience_id' => $audience_id,
        ]);

        return response()->json([
            'success' => true,
            'stub' => true
        ], 200);
    }
}
