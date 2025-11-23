<?php

namespace App\Http\Controllers\AdPlatform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\AdPlatform\AdAudience;
use Illuminate\Http\JsonResponse;
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
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        Log::info('AdAudienceController::index called (stub)', [
            'filters' => $request->all(),
        ]);

        return $this->success([
            'data' => [],
            'pagination' => ['total' => 0, 'per_page' => 15, 'current_page' => 1],
            'stub' => true
        ], 'Ad audiences retrieved successfully');
    }

    /**
     * Return form/metadata for creating a new ad audience
     *
     * @param Request $request HTTP request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        Log::info('AdAudienceController::create called (stub)');

        return $this->success([
            'form_fields' => [],
            'stub' => true
        ], 'Form metadata retrieved successfully');
    }

    /**
     * Store a newly created ad audience in database
     *
     * @param Request $request HTTP request with ad audience data
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        Log::info('AdAudienceController::store called (stub)', [
            'data' => $request->all(),
        ]);

        return $this->created([
            'id' => 'ad_audience_stub_' . uniqid(),
            'stub' => true
        ], 'Ad audience created successfully');
    }

    /**
     * Display the specified ad audience by ID
     *
     * @param Request $request HTTP request
     * @param string $audience_id Ad Audience ID
     * @return JsonResponse
     */
    public function show(Request $request, $audience_id): JsonResponse
    {
        Log::info('AdAudienceController::show called (stub)', [
            'audience_id' => $audience_id,
        ]);

        return $this->success([
            'id' => $audience_id,
            'stub' => true
        ], 'Ad audience retrieved successfully');
    }

    /**
     * Return form/metadata for editing the specified ad audience
     *
     * @param Request $request HTTP request
     * @param string $audience_id Ad Audience ID
     * @return JsonResponse
     */
    public function edit(Request $request, $audience_id): JsonResponse
    {
        Log::info('AdAudienceController::edit called (stub)', [
            'audience_id' => $audience_id,
        ]);

        return $this->success([
            'form_fields' => ['id' => $audience_id],
            'stub' => true
        ], 'Edit form metadata retrieved successfully');
    }

    /**
     * Update the specified ad audience in database
     *
     * @param Request $request HTTP request with ad audience data
     * @param string $audience_id Ad Audience ID
     * @return JsonResponse
     */
    public function update(Request $request, $audience_id): JsonResponse
    {
        Log::info('AdAudienceController::update called (stub)', [
            'audience_id' => $audience_id,
            'data' => $request->all(),
        ]);

        return $this->success([
            'id' => $audience_id,
            'stub' => true
        ], 'Ad audience updated successfully');
    }

    /**
     * Remove the specified ad audience from database
     *
     * @param Request $request HTTP request
     * @param string $audience_id Ad Audience ID
     * @return JsonResponse
     */
    public function destroy(Request $request, $audience_id): JsonResponse
    {
        Log::info('AdAudienceController::destroy called (stub)', [
            'audience_id' => $audience_id,
        ]);

        return $this->deleted('Ad audience deleted successfully');
    }
}
