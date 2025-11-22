<?php

namespace App\Http\Controllers;

use App\Services\BudgetBiddingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * BudgetController
 *
 * Handles budget and bidding management
 * Implements Sprint 4.4: Budget & Bidding
 */
class BudgetController extends Controller
{
    use ApiResponse;

    protected BudgetBiddingService $budgetService;

    public function __construct(BudgetBiddingService $budgetService)
    {
        $this->middleware('auth:sanctum');
        $this->budgetService = $budgetService;
    }

    /**
     * Update campaign budget
     * PUT /api/orgs/{org_id}/budget/campaign/{campaign_id}
     */
    public function updateCampaignBudget(string $orgId, string $campaignId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'budget_type' => 'required|in:daily,lifetime',
            'daily_budget' => 'required_if:budget_type,daily|nullable|numeric|min:1',
            'lifetime_budget' => 'required_if:budget_type,lifetime|nullable|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->budgetService->updateCampaignBudget($campaignId, $request->all());
        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Update bid strategy
     * PUT /api/orgs/{org_id}/budget/campaign/{campaign_id}/bid-strategy
     */
    public function updateBidStrategy(string $orgId, string $campaignId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bid_strategy' => 'required|in:lowest_cost,cost_cap,bid_cap,target_cost',
            'bid_amount' => 'nullable|numeric|min:0.01'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->budgetService->updateBidStrategy($campaignId, $request->all());
        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Get spend tracking
     * GET /api/orgs/{org_id}/budget/campaign/{campaign_id}/tracking
     */
    public function getSpendTracking(string $orgId, string $campaignId): JsonResponse
    {
        $result = $this->budgetService->getSpendTracking($campaignId);
        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Calculate ROI
     * GET /api/orgs/{org_id}/budget/campaign/{campaign_id}/roi
     */
    public function calculateROI(string $orgId, string $campaignId): JsonResponse
    {
        $result = $this->budgetService->calculateROI($campaignId);
        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Get budget recommendations
     * GET /api/orgs/{org_id}/budget/campaign/{campaign_id}/recommendations
     */
    public function getBudgetRecommendations(string $orgId, string $campaignId): JsonResponse
    {
        $result = $this->budgetService->getBudgetRecommendations($campaignId);
        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Optimize budget allocation
     * POST /api/orgs/{org_id}/budget/optimize
     */
    public function optimizeBudgetAllocation(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ad_account_id' => 'required|uuid',
            'total_budget' => 'required|numeric|min:1',
            'goal' => 'nullable|in:roi,conversions,reach'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->budgetService->optimizeBudgetAllocation(
            $request->input('ad_account_id'),
            $request->only(['total_budget', 'goal'])
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
