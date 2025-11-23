<?php

namespace App\Http\Controllers;

use App\Services\BudgetBiddingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Budget\UpdateCampaignBudgetRequest;
use App\Http\Requests\Budget\UpdateBidStrategyRequest;
use App\Http\Requests\Budget\OptimizeBudgetRequest;

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
    public function updateCampaignBudget(string $orgId, string $campaignId, UpdateCampaignBudgetRequest $request): JsonResponse
    {
        $result = $this->budgetService->updateCampaignBudget($campaignId, $request->validated());
        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Update bid strategy
     * PUT /api/orgs/{org_id}/budget/campaign/{campaign_id}/bid-strategy
     */
    public function updateBidStrategy(string $orgId, string $campaignId, UpdateBidStrategyRequest $request): JsonResponse
    {
        $result = $this->budgetService->updateBidStrategy($campaignId, $request->validated());
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
    public function optimizeBudgetAllocation(string $orgId, OptimizeBudgetRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->budgetService->optimizeBudgetAllocation(
            $validated['ad_account_id'],
            collect($validated)->only(['total_budget', 'goal'])->toArray()
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
