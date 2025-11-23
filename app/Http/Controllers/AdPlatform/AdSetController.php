<?php

namespace App\Http\Controllers\AdPlatform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\AdPlatform\AdSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Ad Set Controller
 *
 * Handles HTTP requests for ad set management
 * Note: Stub implementation
 */
class AdSetController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of ad sets with filtering and pagination
     *
     * @param Request $request HTTP request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        Log::info('AdSetController::index called (stub)', [
            'filters' => $request->all(),
        ]);

        return $this->success([
            'data' => [],
            'pagination' => ['total' => 0, 'per_page' => 15, 'current_page' => 1],
            'stub' => true
        ], 'Ad sets retrieved successfully');
    }

    /**
     * Return form/metadata for creating a new ad set
     *
     * @param Request $request HTTP request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        Log::info('AdSetController::create called (stub)');

        return $this->success([
            'form_fields' => [],
            'stub' => true
        ], 'Form metadata retrieved successfully');
    }

    /**
     * Store a newly created ad set in database
     *
     * @param Request $request HTTP request with ad set data
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        Log::info('AdSetController::store called (stub)', [
            'data' => $request->all(),
        ]);

        return $this->created([
            'id' => 'ad_set_stub_' . uniqid(),
            'stub' => true
        ], 'Ad set created successfully');
    }

    /**
     * Display the specified ad set by ID
     *
     * @param Request $request HTTP request
     * @param string $adset_id Ad Set ID
     * @return JsonResponse
     */
    public function show(Request $request, $adset_id): JsonResponse
    {
        Log::info('AdSetController::show called (stub)', [
            'adset_id' => $adset_id,
        ]);

        return $this->success([
            'id' => $adset_id,
            'stub' => true
        ], 'Ad set retrieved successfully');
    }

    /**
     * Return form/metadata for editing the specified ad set
     *
     * @param Request $request HTTP request
     * @param string $adset_id Ad Set ID
     * @return JsonResponse
     */
    public function edit(Request $request, $adset_id): JsonResponse
    {
        Log::info('AdSetController::edit called (stub)', [
            'adset_id' => $adset_id,
        ]);

        return $this->success([
            'form_fields' => ['id' => $adset_id],
            'stub' => true
        ], 'Edit form metadata retrieved successfully');
    }

    /**
     * Update the specified ad set in database
     *
     * @param Request $request HTTP request with ad set data
     * @param string $adset_id Ad Set ID
     * @return JsonResponse
     */
    public function update(Request $request, $adset_id): JsonResponse
    {
        Log::info('AdSetController::update called (stub)', [
            'adset_id' => $adset_id,
            'data' => $request->all(),
        ]);

        return $this->success([
            'id' => $adset_id,
            'stub' => true
        ], 'Ad set updated successfully');
    }

    /**
     * Remove the specified ad set from database
     *
     * @param Request $request HTTP request
     * @param string $adset_id Ad Set ID
     * @return JsonResponse
     */
    public function destroy(Request $request, $adset_id): JsonResponse
    {
        Log::info('AdSetController::destroy called (stub)', [
            'adset_id' => $adset_id,
        ]);

        return $this->deleted('Ad set deleted successfully');
    }
}
