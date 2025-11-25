<?php

namespace App\Http\Controllers\AdPlatform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\AdPlatform\AdAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Ad Account Controller
 *
 * Handles HTTP requests for ad account management
 * Note: Stub implementation
 */
class AdAccountController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of ad accounts with filtering and pagination
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        Log::info('AdAccountController::index called (stub)', [
            'filters' => $request->all(),
        ]);

        return response()->json([
            'data' => [],
            'pagination' => ['total' => 0, 'per_page' => 15, 'current_page' => 1],
            'stub' => true
        ], 200);
    }

    /**
     * Return form/metadata for creating a new ad account
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        Log::info('AdAccountController::create called (stub)');

        return response()->json([
            'form_fields' => [],
            'stub' => true
        ], 200);
    }

    /**
     * Store a newly created ad account in database
     *
     * @param Request $request HTTP request with ad account data
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('AdAccountController::store called (stub)', [
            'data' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'id' => 'ad_account_stub_' . uniqid(),
            'stub' => true
        ], 201);
    }

    /**
     * Display the specified ad account by ID
     *
     * @param Request $request HTTP request
     * @param string $account_id Ad Account ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $account_id)
    {
        Log::info('AdAccountController::show called (stub)', [
            'account_id' => $account_id,
        ]);

        return response()->json([
            'data' => ['id' => $account_id],
            'stub' => true
        ], 200);
    }

    /**
     * Return form/metadata for editing the specified ad account
     *
     * @param Request $request HTTP request
     * @param string $account_id Ad Account ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request, $account_id)
    {
        Log::info('AdAccountController::edit called (stub)', [
            'account_id' => $account_id,
        ]);

        return response()->json([
            'form_fields' => ['id' => $account_id],
            'stub' => true
        ], 200);
    }

    /**
     * Update the specified ad account in database
     *
     * @param Request $request HTTP request with ad account data
     * @param string $account_id Ad Account ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $account_id)
    {
        Log::info('AdAccountController::update called (stub)', [
            'account_id' => $account_id,
            'data' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'id' => $account_id,
            'stub' => true
        ], 200);
    }

    /**
     * Remove the specified ad account from database
     *
     * @param Request $request HTTP request
     * @param string $account_id Ad Account ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $account_id)
    {
        Log::info('AdAccountController::destroy called (stub)', [
            'account_id' => $account_id,
        ]);

        return response()->json([
            'success' => true,
            'stub' => true
        ], 200);
    }
}
