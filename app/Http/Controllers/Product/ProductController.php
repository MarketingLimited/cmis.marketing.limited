<?php

namespace App\Http\Controllers\Product;
use App\Http\Controllers\Concerns\ApiResponse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

/**
 * Product Controller
 *
 * Handles HTTP requests for product management
 * Note: Stub implementation
 */
class ProductController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of products with filtering and pagination
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        Log::info('ProductController::index called (stub)', [
            'filters' => $request->all(),
        ]);

        return response()->json([
            'data' => [],
            'pagination' => ['total' => 0, 'per_page' => 15, 'current_page' => 1],
            'stub' => true
        ], 200);
    }

    /**
     * Store a newly created product in database
     *
     * @param Request $request HTTP request with product data
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        Log::info('ProductController::store called (stub)', [
            'data' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'id' => 'product_stub_' . uniqid(),
            'stub' => true
        ], 201);
    }

    /**
     * Display the specified product by offering ID
     *
     * @param Request $request HTTP request
     * @param string $offering_id Offering ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $offering_id): JsonResponse
    {
        Log::info('ProductController::show called (stub)', [
            'offering_id' => $offering_id,
        ]);

        return response()->json([
            'data' => ['offering_id' => $offering_id],
            'stub' => true
        ], 200);
    }

    /**
     * Update the specified product in database
     *
     * @param Request $request HTTP request with product data
     * @param string $offering_id Offering ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $offering_id): JsonResponse
    {
        Log::info('ProductController::update called (stub)', [
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
     * Remove the specified product from database
     *
     * @param Request $request HTTP request
     * @param string $offering_id Offering ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $offering_id): JsonResponse
    {
        Log::info('ProductController::destroy called (stub)', [
            'offering_id' => $offering_id,
        ]);

        return response()->json([
            'success' => true,
            'stub' => true
        ], 200);
    }
}
