<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\ApiResponse;

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

        if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Failed to update campaign budget');
        }

        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Campaign budget updated successfully');
    }

    /**
     * Update bid strategy
     * PUT /api/orgs/{org_id}/budget/campaign/{campaign_id}/bid-strategy
     */
    public function updateBidStrategy(string $orgId, string $campaignId, UpdateBidStrategyRequest $request): JsonResponse
    {
        $result = $this->budgetService->updateBidStrategy($campaignId, $request->validated());

        if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Failed to update bid strategy');
        }

        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Bid strategy updated successfully');
    }

    /**
     * Get spend tracking
     * GET /api/orgs/{org_id}/budget/campaign/{campaign_id}/tracking
     */
    public function getSpendTracking(string $orgId, string $campaignId): JsonResponse
    {
        $result = $this->budgetService->getSpendTracking($campaignId);

        if (!$result['success']) {
            return $this->notFound($result['message'] ?? 'Spend tracking not found');
        }

        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Spend tracking retrieved successfully');
    }

    /**
     * Calculate ROI
     * GET /api/orgs/{org_id}/budget/campaign/{campaign_id}/roi
     */
    public function calculateROI(string $orgId, string $campaignId): JsonResponse
    {
        $result = $this->budgetService->calculateROI($campaignId);

        if (!$result['success']) {
            return $this->notFound($result['message'] ?? 'ROI data not found');
        }

        return $this->success($result['data'] ?? $result, $result['message'] ?? 'ROI calculated successfully');
    }

    /**
     * Get budget recommendations
     * GET /api/orgs/{org_id}/budget/campaign/{campaign_id}/recommendations
     */
    public function getBudgetRecommendations(string $orgId, string $campaignId): JsonResponse
    {
        $result = $this->budgetService->getBudgetRecommendations($campaignId);

        if (!$result['success']) {
            return $this->notFound($result['message'] ?? 'Budget recommendations not found');
        }

        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Budget recommendations retrieved successfully');
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

        if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Failed to optimize budget allocation');
        }

        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Budget allocation optimized successfully');
    }
}
