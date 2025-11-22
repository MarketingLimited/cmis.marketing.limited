<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Service Controller
 *
 * Handles HTTP requests for service management
 * Note: Stub implementation
 */
class ServiceController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of services with filtering and pagination
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        Log::info('ServiceController::index called (stub)', [
            'filters' => $request->all(),
        ]);

        return response()->json([
            'data' => [],
            'pagination' => ['total' => 0, 'per_page' => 15, 'current_page' => 1],
            'stub' => true
        ], 200);
    }

    /**
     * Store a newly created service in database
     *
     * @param Request $request HTTP request with service data
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('ServiceController::store called (stub)', [
            'data' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'id' => 'service_stub_' . uniqid(),
            'stub' => true
        ], 201);
    }

    /**
     * Display the specified service by offering ID
     *
     * @param Request $request HTTP request
     * @param string $offering_id Offering ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $offering_id)
    {
        Log::info('ServiceController::show called (stub)', [
            'offering_id' => $offering_id,
        ]);

        return response()->json([
            'data' => ['offering_id' => $offering_id],
            'stub' => true
        ], 200);
    }

    /**
     * Update the specified service in database
     *
     * @param Request $request HTTP request with service data
     * @param string $offering_id Offering ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $offering_id)
    {
        Log::info('ServiceController::update called (stub)', [
            'offering_id' => $offering_id,
            'data' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'id' => $offering_id,
            'stub' => true
        ], 200);
    }

    /**
     * Remove the specified service from database
     *
     * @param Request $request HTTP request
     * @param string $offering_id Offering ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $offering_id)
    {
        Log::info('ServiceController::destroy called (stub)', [
            'offering_id' => $offering_id,
        ]);

        return response()->json([
            'success' => true,
            'stub' => true
        ], 200);
    }
}
