<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Offering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Offering Controller
 *
 * Handles HTTP requests for offering management
 * Note: Stub implementation
 */
class OfferingController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of offerings with filtering and pagination
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        Log::info('OfferingController::index called (stub)', [
            'filters' => $request->all(),
        ]);

        return response()->json([
            'data' => [],
            'pagination' => ['total' => 0, 'per_page' => 15, 'current_page' => 1],
            'stub' => true
        ], 200);
    }

    /**
     * Return form/metadata for creating a new offering
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        Log::info('OfferingController::create called (stub)');

        return response()->json([
            'form_fields' => [],
            'stub' => true
        ], 200);
    }

    /**
     * Store a newly created offering in database
     *
     * @param Request $request HTTP request with offering data
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('OfferingController::store called (stub)', [
            'data' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'id' => 'offering_stub_' . uniqid(),
            'stub' => true
        ], 201);
    }

    /**
     * Display the specified offering by ID
     *
     * @param Request $request HTTP request
     * @param string $offering_id Offering ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $offering_id)
    {
        Log::info('OfferingController::show called (stub)', [
            'offering_id' => $offering_id,
        ]);

        return response()->json([
            'data' => ['id' => $offering_id],
            'stub' => true
        ], 200);
    }

    /**
     * Return form/metadata for editing the specified offering
     *
     * @param Request $request HTTP request
     * @param string $offering_id Offering ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request, $offering_id)
    {
        Log::info('OfferingController::edit called (stub)', [
            'offering_id' => $offering_id,
        ]);

        return response()->json([
            'form_fields' => ['id' => $offering_id],
            'stub' => true
        ], 200);
    }

    /**
     * Update the specified offering in database
     *
     * @param Request $request HTTP request with offering data
     * @param string $offering_id Offering ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $offering_id)
    {
        Log::info('OfferingController::update called (stub)', [
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
     * Remove the specified offering from database
     *
     * @param Request $request HTTP request
     * @param string $offering_id Offering ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $offering_id)
    {
        Log::info('OfferingController::destroy called (stub)', [
            'offering_id' => $offering_id,
        ]);

        return response()->json([
            'success' => true,
            'stub' => true
        ], 200);
    }
}
