<?php

namespace App\Http\Controllers\AdPlatform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\AdPlatform\AdAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\AdPlatform\StoreAdAccountRequest;
use App\Http\Requests\AdPlatform\UpdateAdAccountRequest;

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
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        Log::info('AdAccountController::index called (stub)', [
            'filters' => $request->all(),
        ]);

        return $this->success([
            'data' => [],
            'pagination' => ['total' => 0, 'per_page' => 15, 'current_page' => 1],
            'stub' => true
        ], 'Ad accounts retrieved successfully');
    }

    /**
     * Return form/metadata for creating a new ad account
     *
     * @param Request $request HTTP request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        Log::info('AdAccountController::create called (stub)');

        return $this->success([
            'form_fields' => [],
            'stub' => true
        ], 'Form metadata retrieved successfully');
    }

    /**
     * Store a newly created ad account in database
     *
     * @param StoreAdAccountRequest $request HTTP request with ad account data
     * @return JsonResponse
     */
    public function store(StoreAdAccountRequest $request): JsonResponse
    {
        Log::info('AdAccountController::store called (stub)', [
            'data' => $request->all(),
        ]);

        return $this->created([
            'id' => 'ad_account_stub_' . uniqid(),
            'stub' => true
        ], 'Ad account created successfully');
    }

    /**
     * Display the specified ad account by ID
     *
     * @param Request $request HTTP request
     * @param string $account_id Ad Account ID
     * @return JsonResponse
     */
    public function show(Request $request, $account_id): JsonResponse
    {
        Log::info('AdAccountController::show called (stub)', [
            'account_id' => $account_id,
        ]);

        return $this->success([
            'id' => $account_id,
            'stub' => true
        ], 'Ad account retrieved successfully');
    }

    /**
     * Return form/metadata for editing the specified ad account
     *
     * @param Request $request HTTP request
     * @param string $account_id Ad Account ID
     * @return JsonResponse
     */
    public function edit(Request $request, $account_id): JsonResponse
    {
        Log::info('AdAccountController::edit called (stub)', [
            'account_id' => $account_id,
        ]);

        return $this->success([
            'form_fields' => ['id' => $account_id],
            'stub' => true
        ], 'Edit form metadata retrieved successfully');
    }

    /**
     * Update the specified ad account in database
     *
     * @param UpdateAdAccountRequest $request HTTP request with ad account data
     * @param string $account_id Ad Account ID
     * @return JsonResponse
     */
    public function update(UpdateAdAccountRequest $request, $account_id): JsonResponse
    {
        Log::info('AdAccountController::update called (stub)', [
            'account_id' => $account_id,
            'data' => $request->all(),
        ]);

        return $this->success([
            'id' => $account_id,
            'stub' => true
        ], 'Ad account updated successfully');
    }

    /**
     * Remove the specified ad account from database
     *
     * @param Request $request HTTP request
     * @param string $account_id Ad Account ID
     * @return JsonResponse
     */
    public function destroy(Request $request, $account_id): JsonResponse
    {
        Log::info('AdAccountController::destroy called (stub)', [
            'account_id' => $account_id,
        ]);

        return $this->deleted('Ad account deleted successfully');
    }
}
